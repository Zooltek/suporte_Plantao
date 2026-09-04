<?php

declare(strict_types=1);

namespace App\Services\Integration;

use App\Contracts\Repositories\CustomerGroupRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Models\Customer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

/**
 * Orquestra o cadastro e a situação de clientes vindos do sistema financeiro.
 *
 * Não acessa o banco diretamente — delega aos repositories. O mapeamento do
 * payload do financeiro para os atributos do model fica concentrado aqui.
 *
 * Contrato de payload atual (v2):
 * {
 *   "id": 154,
 *   "name": "...",
 *   "cnpj": "...",
 *   "state_registration": "64007281-0",   // Inscrição Estadual
 *   "city_registration": "3205309",        // Código IBGE do município
 *   "business_group": {
 *     "code": "BFC27A6401563A",
 *     "name": "Grupo Empresarial Exemplo"
 *   },
 *   "contacts": [
 *     {"name": "Contato Comunicação", "email": "comunicacao@empresa.com.br"},
 *     {"name": "Contato Cobrança", "email": "cobranca@empresa.com.br"}
 *   ],
 *   ...
 * }
 */
class CustomerIntegrationService
{
    public function __construct(
        private readonly CustomerRepositoryInterface $customers,
        private readonly StateRepositoryInterface $states,
        private readonly CustomerGroupRepositoryInterface $customerGroups,
    ) {}

    /**
     * Cadastra (ou atualiza, em caso de reenvio) um cliente do financeiro.
     */
    public function register(array $data): Customer
    {
        return $this->customers->transaction(
            function () use ($data): Customer {
                $customer = $this->customers->upsertByFinanceiroId(
                    (int) $data['id'],
                    $this->mapAttributes($data),
                );

                $contacts = $this->financialContactsFrom($data);

                if ($contacts !== null) {
                    $this->customers->syncFinancialContacts($customer, $contacts);
                }

                return $customer;
            },
        );
    }

    public function inactivate(int $financeiroId): Customer
    {
        return $this->changeActiveStatus($financeiroId, false);
    }

    public function reactivate(int $financeiroId): Customer
    {
        return $this->changeActiveStatus($financeiroId, true);
    }

    private function changeActiveStatus(int $financeiroId, bool $active): Customer
    {
        $customer = $this->customers->setActiveStatus($financeiroId, $active);

        if ($customer === null) {
            throw (new ModelNotFoundException)->setModel(Customer::class, [$financeiroId]);
        }

        return $customer;
    }

    /**
     * Traduz o payload do financeiro para os atributos persistidos no model.
     * Campos ausentes não são incluídos, preservando dados em atualizações parciais.
     *
     * @return array<string, mixed>
     */
    private function mapAttributes(array $data): array
    {
        $address = Collection::make([
            $data['logradouro'] ?? null,
            $data['numero'] ?? null,
            $data['complemento'] ?? null,
        ])->filter()->implode(', ');

        $attributes = [
            'name' => $data['name'] ?? null,
            'trade_name' => $data['trade_name'] ?? null,
            'cnpj' => isset($data['cnpj']) ? $this->onlyDigits($data['cnpj']) : null,
            'city_registration' => $data['city_registration'] ?? null,
            'state_registration' => $data['state_registration'] ?? null,
            'phone' => $data['telephone_1'] ?? null,
            'telephone_2' => $data['telephone_2'] ?? null,
            'address' => $address !== '' ? $address : null,
            'bairro' => $data['bairro'] ?? null,
            'city' => $data['city'] ?? null,
            'postal_code' => isset($data['postal_code']) ? $this->onlyDigits($data['postal_code']) : null,
            'observations' => $data['notes_2'] ?? null,
            'email' => $data['email'] ?? null,
            'software_id' => $data['software_id'] ?? null,
            'contact_name' => $data['contact_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'state_id' => isset($data['state_id'])
                ? $this->states->findIdByIbgeCode((int) $data['state_id'])
                : null,
            'customer_group_id' => $this->resolveGroupId($data['business_group']),
        ];

        $attributes = array_filter(
            $attributes,
            static fn ($value) => $value !== null && $value !== '',
        );

        // O novo objeto é a fonte preferencial. Mantém os campos legados
        // coerentes para consumidores que ainda não migraram para `contacts`.
        if (array_key_exists('contacts', $data) && is_array($data['contacts'])) {
            $contacts = $this->normalizeFinancialContacts($data['contacts']);
            $attributes['contact_name'] = $contacts[0]['name'] ?? null;
            $attributes['contact_email'] = $contacts[0]['email'] ?? null;
        }

        return $attributes;
    }

    /**
     * @return array<int, array{name: string, email: string|null}>|null
     */
    private function financialContactsFrom(array $data): ?array
    {
        if (array_key_exists('contacts', $data) && is_array($data['contacts'])) {
            return $this->normalizeFinancialContacts($data['contacts']);
        }

        if (! array_key_exists('contact_name', $data) && ! array_key_exists('contact_email', $data)) {
            return null;
        }

        $name = trim((string) ($data['contact_name'] ?? ''));
        $email = trim((string) ($data['contact_email'] ?? ''));

        if ($name === '' && $email === '') {
            return [];
        }

        return [[
            'name' => $name !== '' ? $name : $email,
            'email' => $email !== '' ? $email : null,
        ]];
    }

    /**
     * @param  array<int, array{name?: string|null, email?: string|null}>  $contacts
     * @return array<int, array{name: string, email: string|null}>
     */
    private function normalizeFinancialContacts(array $contacts): array
    {
        return Collection::make($contacts)
            ->map(static function (array $contact): array {
                $name = trim((string) ($contact['name'] ?? ''));
                $email = trim((string) ($contact['email'] ?? ''));

                return [
                    'name' => $name !== '' ? $name : $email,
                    'email' => $email !== '' ? $email : null,
                ];
            })
            ->filter(static fn (array $contact): bool => $contact['name'] !== '')
            ->values()
            ->all();
    }

    /**
     * Resolve o ID do grupo empresarial a partir do objeto `business_group` do payload.
     *
     * @param  array{code: string, name: string}  $businessGroup
     */
    private function resolveGroupId(array $businessGroup): int
    {
        return $this->customerGroups
            ->upsertByFinancialCode($businessGroup['code'], $businessGroup['name'])
            ->id;
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }
}
