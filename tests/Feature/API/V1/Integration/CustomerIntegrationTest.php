<?php

use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Models\State;
use App\Services\Integration\CustomerIntegrationService;
use Illuminate\Database\QueryException;

// ─────────────────────────────────────────────────────────────────────────────
// Integração Financeiro — cadastro e situação de clientes (fluxo completo)
// Payload v2: business_group, state_registration, city_registration (IBGE)
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    config(['services.financeiro.api_key' => 'test-integration-key']);
});

function integrationHeaders(): array
{
    return ['X-API-Key' => 'test-integration-key'];
}

/**
 * Payload v2 base — espelha o contrato acordado com o sistema financeiro.
 */
function financeiroPayload(array $overrides = []): array
{
    return array_merge([
        'id' => 154,
        'name' => 'Isabelle e Alícia Joalheria ME',
        'trade_name' => 'Isabelle e Alícia Joalheria ME',
        'cnpj' => '18587938000115',
        'city_registration' => '3205309',
        'state_registration' => '64007281-0',
        'telephone_1' => '2725929137',
        'telephone_2' => '27983743841',
        'logradouro' => 'Beco Almir Barbosa',
        'numero' => '685',
        'complemento' => '',
        'bairro' => 'Jesus de Nazareth',
        'city' => 'Vitória',
        'state_id' => 32,
        'postal_code' => '29052046',
        'notes_2' => '',
        'email' => 'contato@empresa.com.br',
        'contact_name' => 'João Silva',
        'contact_email' => 'joao.silva@empresa.com.br',
        'contacts' => [
            ['name' => 'Maria Comunicação', 'email' => 'maria.comunicacao@empresa.com.br'],
            ['name' => 'Carlos Cobrança', 'email' => 'carlos.cobranca@empresa.com.br'],
        ],
        'business_group' => [
            'code' => 'BFC27A6401563A',
            'name' => 'Grupo Empresarial Exemplo',
        ],
    ], $overrides);
}

describe('POST /api/v1/integration/customers', function () {

    it('cria o cliente mapeando os campos do payload v2', function () {
        $es = State::create(['name' => 'Espírito Santo', 'abbreviation' => 'ES']);
        $software = \App\Models\Software::factory()->create();

        $this->postJson('/api/v1/integration/customers', financeiroPayload(['software_id' => $software->id]), integrationHeaders())
            ->assertStatus(201)
            ->assertJsonPath('data.financeiro_id', 154)
            ->assertJsonPath('data.financial_irregular', false);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();

        expect($customer->name)->toBe('Isabelle e Alícia Joalheria ME')
            ->and($customer->cnpj)->toBe('18587938000115')
            ->and($customer->city_registration)->toBe('3205309')          // Código IBGE
            ->and($customer->state_registration)->toBe('64007281-0')      // IE
            ->and($customer->phone)->toBe('2725929137')
            ->and($customer->telephone_2)->toBe('27983743841')
            ->and($customer->address)->toBe('Beco Almir Barbosa, 685')
            ->and($customer->bairro)->toBe('Jesus de Nazareth')
            ->and($customer->city)->toBe('Vitória')
            ->and($customer->postal_code)->toBe('29052046')
            ->and($customer->state_id)->toBe($es->id)
            ->and($customer->email)->toBe('contato@empresa.com.br')
            ->and($customer->software_id)->toBe($software->id)
            ->and($customer->contact_name)->toBe('Maria Comunicação')
            ->and($customer->contact_email)->toBe('maria.comunicacao@empresa.com.br')
            ->and($customer->contacts()->where('origin', 'financeiro')->count())->toBe(2)
            ->and($customer->contacts()->where('origin', 'financeiro')->pluck('email')->all())->toBe([
                'maria.comunicacao@empresa.com.br',
                'carlos.cobranca@empresa.com.br',
            ]);
    });

    it('aceita no máximo dois contatos enviados pelo financeiro', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'contacts' => [
                ['name' => 'Contato 1', 'email' => 'contato1@empresa.com.br'],
                ['name' => 'Contato 2', 'email' => 'contato2@empresa.com.br'],
                ['name' => 'Contato 3', 'email' => 'contato3@empresa.com.br'],
            ],
        ]), integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['contacts']);
    });

    it('aceita contatos e e-mails opcionais e ignora entradas vazias', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'contacts' => [
                ['name' => 'Contato sem e-mail'],
                ['email' => 'somente.email@empresa.com.br'],
            ],
        ]), integrationHeaders())->assertStatus(201);

        $contacts = Customer::where('financeiro_id', 154)
            ->firstOrFail()
            ->contacts()
            ->where('origin', 'financeiro')
            ->get();

        expect($contacts)->toHaveCount(2)
            ->and($contacts[0]->name)->toBe('Contato sem e-mail')
            ->and($contacts[0]->email)->toBeNull()
            ->and($contacts[1]->name)->toBe('somente.email@empresa.com.br')
            ->and($contacts[1]->email)->toBe('somente.email@empresa.com.br');
    });

    it('mantém contatos dos atendentes ao substituir contatos do financeiro', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();
        $customer->contacts()->create([
            'name' => 'Contato do Suporte',
            'phone' => '(27) 99999-9999',
            'email' => 'suporte@empresa.com.br',
            'origin' => 'support',
            'is_main' => true,
        ]);

        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'contacts' => [
                ['name' => 'Contato Financeiro Atualizado', 'email' => 'atualizado@empresa.com.br'],
            ],
        ]), integrationHeaders())->assertStatus(200);

        expect($customer->contacts()->where('origin', 'support')->pluck('name')->all())
            ->toBe(['Contato do Suporte'])
            ->and($customer->contacts()->where('origin', 'financeiro')->pluck('name')->all())
            ->toBe(['Contato Financeiro Atualizado']);
    });

    it('inclui contact_name e contact_email legados na lista de contatos', function () {
        $payload = financeiroPayload();
        unset($payload['contacts']);

        $this->postJson('/api/v1/integration/customers', $payload, integrationHeaders())
            ->assertStatus(201);

        $contact = Customer::where('financeiro_id', 154)
            ->firstOrFail()
            ->contacts()
            ->where('origin', 'financeiro')
            ->firstOrFail();

        expect($contact->name)->toBe('João Silva')
            ->and($contact->email)->toBe('joao.silva@empresa.com.br');
    });

    it('remove somente contatos do financeiro quando contacts é enviado vazio', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();
        $customer->contacts()->create([
            'name' => 'Contato do Atendente',
            'phone' => '(27) 98888-7777',
            'origin' => 'support',
            'is_main' => true,
        ]);

        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'contacts' => [],
            'contact_name' => null,
            'contact_email' => null,
        ]), integrationHeaders())->assertStatus(200);

        expect($customer->contacts()->where('origin', 'financeiro')->exists())->toBeFalse()
            ->and($customer->contacts()->where('origin', 'support')->pluck('name')->all())
            ->toBe(['Contato do Atendente']);
    });

    it('cria e vincula o grupo empresarial pelo financial_code', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();
        $group = CustomerGroup::where('financial_code', 'BFC27A6401563A')->first();

        expect($group)->not->toBeNull()
            ->and($group->name)->toBe('Grupo Empresarial Exemplo')
            ->and($customer->customer_group_id)->toBe($group->id);
    });

    it('não persiste codigo_empresarial do contrato legado', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'codigo_empresarial' => 'CODIGO-LEGADO',
        ]), integrationHeaders())->assertStatus(201);

        expect(Customer::where('financeiro_id', 154)->value('codigo_empresarial'))->toBeNull();
    });

    it('atualiza o nome do grupo quando mudou no financeiro', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'id' => 200,
            'cnpj' => '99999999000199',
            'email' => 'outra.empresa@empresa.com.br',
            'business_group' => ['code' => 'BFC27A6401563A', 'name' => 'Nome Atualizado'],
        ]), integrationHeaders())->assertStatus(201);

        expect(CustomerGroup::where('financial_code', 'BFC27A6401563A')->value('name'))
            ->toBe('Nome Atualizado');
    });

    it('reverte a atualização do grupo quando a empresa não pode ser persistida', function () {
        $group = CustomerGroup::query()->create([
            'financial_code' => 'GROUP-ROLLBACK',
            'name' => 'Nome Original',
            'hash' => 'group-rollback-test',
            'status' => true,
        ]);

        Customer::query()->create([
            'financeiro_id' => 900,
            'name' => 'Empresa Existente',
            'cnpj' => '11111111000111',
        ]);

        expect(fn () => app(CustomerIntegrationService::class)->register([
            'id' => 901,
            'name' => 'Empresa com CNPJ Duplicado',
            'cnpj' => '11111111000111',
            'business_group' => [
                'code' => 'GROUP-ROLLBACK',
                'name' => 'Nome que deve ser revertido',
            ],
        ]))->toThrow(QueryException::class);

        expect($group->fresh()->name)->toBe('Nome Original')
            ->and(Customer::query()->where('financeiro_id', 901)->exists())->toBeFalse();
    });

    it('exige business_group no cadastro e na alteração', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'business_group' => null,
        ]), integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['business_group']);
    });

    it('é idempotente: reenviar o mesmo id atualiza em vez de duplicar (200)', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $this->postJson('/api/v1/integration/customers', financeiroPayload(['name' => 'Novo Nome ME']), integrationHeaders())
            ->assertStatus(200);

        expect(Customer::where('financeiro_id', 154)->count())->toBe(1)
            ->and(Customer::where('financeiro_id', 154)->value('name'))->toBe('Novo Nome ME');
    });

    it('retorna 422 quando faltam campos obrigatórios', function () {
        $this->postJson('/api/v1/integration/customers', ['trade_name' => 'X'], integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id', 'name', 'cnpj']);
    });

    it('retorna 422 quando business_group.code está ausente', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'business_group' => ['name' => 'Sem code'],
        ]), integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['business_group.code']);
    });

    it('retorna 422 quando city_registration não é um código IBGE municipal', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload([
            'city_registration' => '64007281-0',
        ]), integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['city_registration']);
    });

    it('retorna 422 em JSON mesmo quando o cliente HTTP não envia Accept JSON', function () {
        $response = $this->call(
            'POST',
            '/api/v1/integration/customers',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => '*/*',
                'HTTP_X_API_KEY' => 'test-integration-key',
            ],
            json_encode(['trade_name' => 'X'], JSON_THROW_ON_ERROR),
        );

        $response->assertStatus(422)
            ->assertHeader('content-type', 'application/json')
            ->assertJsonValidationErrors(['id', 'name', 'cnpj']);
    });

    it('retorna 422 quando software_id não existe', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(['software_id' => 9999]), integrationHeaders())
            ->assertStatus(422)
            ->assertJsonValidationErrors(['software_id']);
    });

    it('grava state_id nulo quando o código IBGE é desconhecido', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(['state_id' => 99]), integrationHeaders())
            ->assertStatus(201);

        expect(Customer::where('financeiro_id', 154)->value('state_id'))->toBeNull();
    });

});

describe('PATCH inactivate / reactivate', function () {

    it('inativa o cliente (is_active = false)', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $this->patchJson('/api/v1/integration/customers/154/inactivate', [], integrationHeaders())
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.financial_irregular', false);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();

        expect((bool) $customer->is_active)->toBeFalse()
            ->and((bool) $customer->financial_irregular)->toBeFalse();
    });

    it('reativa o cliente (is_active = true)', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        $this->patchJson('/api/v1/integration/customers/154/inactivate', [], integrationHeaders())
            ->assertStatus(200);

        $this->patchJson('/api/v1/integration/customers/154/reactivate', [], integrationHeaders())
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.financial_irregular', false);

        expect((bool) Customer::where('financeiro_id', 154)->value('is_active'))->toBeTrue();
    });

    it('preserva inadimplência ao suspender e reativar o contrato', function () {
        $this->postJson('/api/v1/integration/customers', financeiroPayload(), integrationHeaders())
            ->assertStatus(201);

        Customer::where('financeiro_id', 154)->update(['financial_irregular' => true]);

        $this->patchJson('/api/v1/integration/customers/154/inactivate', [], integrationHeaders())
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.financial_irregular', true);

        $this->patchJson('/api/v1/integration/customers/154/reactivate', [], integrationHeaders())
            ->assertStatus(200)
            ->assertJsonPath('data.is_active', true)
            ->assertJsonPath('data.financial_irregular', true);

        $customer = Customer::where('financeiro_id', 154)->firstOrFail();

        expect((bool) $customer->is_active)->toBeTrue()
            ->and((bool) $customer->financial_irregular)->toBeTrue();
    });

    it('retorna 404 ao inativar id inexistente', function () {
        $this->patchJson('/api/v1/integration/customers/999999/inactivate', [], integrationHeaders())
            ->assertStatus(404);
    });

});
