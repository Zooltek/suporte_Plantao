<?php

declare(strict_types=1);

namespace App\Http\Requests\Integration;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validação do payload de cadastro de cliente enviado pelo sistema financeiro.
 *
 * A autorização é feita pelo middleware EnsureIntegrationApiKey (API key M2M);
 * por isso authorize() retorna true.
 *
 * Payload v2 — campos alterados em relação à v1:
 *   - `codigo_empresarial`  → removido (substituído por `business_group`)
 *   - `city_registration`   → agora representa o Código IBGE do município
 *   - `state_registration`  → novo campo para Inscrição Estadual (IE)
 *   - `business_group`      → novo objeto com `code` e `name` do Grupo Empresarial
 *   - `contacts`            → até dois contatos mantidos pelo Financeiro
 */
class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'id' => ['required', 'integer', 'min:1'],
            'name' => ['required', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'cnpj' => ['required', 'string', 'max:20'],
            'city_registration' => ['nullable', 'string', 'regex:/^\d{7}$/'],
            'state_registration' => ['nullable', 'string', 'max:50'],
            'telephone_1' => ['nullable', 'string', 'max:30'],
            'telephone_2' => ['nullable', 'string', 'max:30'],
            'logradouro' => ['nullable', 'string', 'max:255'],
            'numero' => ['nullable', 'string', 'max:30'],
            'complemento' => ['nullable', 'string', 'max:100'],
            'bairro' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'state_id' => ['nullable', 'integer'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'notes_2' => ['nullable', 'string'],
            'email' => ['nullable', 'string', 'email', 'max:150'],
            'software_id' => ['nullable', 'integer', 'exists:softwares,id'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'string', 'email', 'max:255'],
            'contacts' => ['nullable', 'array', 'max:2'],
            'contacts.*' => ['array:name,email'],
            'contacts.*.name' => ['nullable', 'string', 'max:255'],
            'contacts.*.email' => ['nullable', 'string', 'email', 'max:255'],
            'business_group' => ['required', 'array:code,name'],
            'business_group.code' => ['required', 'string', 'max:100'],
            'business_group.name' => ['required', 'string', 'max:150'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $businessGroup = $this->input('business_group');

        if (is_array($businessGroup)) {
            $this->merge([
                'business_group' => [
                    'code' => isset($businessGroup['code']) && is_string($businessGroup['code'])
                        ? trim($businessGroup['code'])
                        : null,
                    'name' => isset($businessGroup['name']) && is_string($businessGroup['name'])
                        ? trim($businessGroup['name'])
                        : null,
                ],
            ]);
        }

        $contacts = $this->input('contacts');

        if (is_array($contacts)) {
            $this->merge([
                'contacts' => array_map(static fn ($contact): mixed => is_array($contact) ? [
                    'name' => isset($contact['name']) && is_string($contact['name'])
                        ? trim($contact['name'])
                        : null,
                    'email' => isset($contact['email']) && is_string($contact['email'])
                        ? trim($contact['email'])
                        : null,
                ] : $contact, $contacts),
            ]);
        }
    }
}
