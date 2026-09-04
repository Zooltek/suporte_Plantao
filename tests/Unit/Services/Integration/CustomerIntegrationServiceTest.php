<?php

use App\Contracts\Repositories\CustomerGroupRepositoryInterface;
use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Contracts\Repositories\StateRepositoryInterface;
use App\Models\Customer;
use App\Models\CustomerGroup;
use App\Services\Integration\CustomerIntegrationService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─────────────────────────────────────────────────────────────────────────────
// CustomerIntegrationService — testes unitários com repositórios mockados
// ─────────────────────────────────────────────────────────────────────────────

function makeService(&$customers, &$states, &$groups): CustomerIntegrationService
{
    $customers = Mockery::mock(CustomerRepositoryInterface::class);
    $states = Mockery::mock(StateRepositoryInterface::class);
    $groups = Mockery::mock(CustomerGroupRepositoryInterface::class);

    return new CustomerIntegrationService($customers, $states, $groups);
}

it('mapeia o payload v2 e resolve estado e grupo ao registrar', function () {
    $service = makeService($customers, $states, $groups);

    $customers->shouldReceive('transaction')
        ->once()
        ->andReturnUsing(static fn (\Closure $callback) => $callback());
    $states->shouldReceive('findIdByIbgeCode')->once()->with(32)->andReturn(8);

    $group = new CustomerGroup;
    $group->id = 5;
    $groups->shouldReceive('upsertByFinancialCode')
        ->once()
        ->with('BFC27A6401563A', 'Grupo Empresarial Exemplo')
        ->andReturn($group);

    $customers->shouldReceive('upsertByFinanceiroId')
        ->once()
        ->withArgs(function (int $financeiroId, array $attributes) {
            return $financeiroId === 154
                && $attributes['cnpj'] === '18587938000115'          // só dígitos
                && $attributes['postal_code'] === '29052046'
                && $attributes['phone'] === '2725929137'             // telephone_1 → phone
                && $attributes['address'] === 'Beco Almir Barbosa, 685'
                && $attributes['observations'] === 'obs'             // notes_2 → observations
                && $attributes['state_id'] === 8                     // IBGE 32 → 8
                && $attributes['email'] === 'contato@empresa.com.br'
                && $attributes['software_id'] === 1
                && $attributes['contact_name'] === 'Maria Comunicação'
                && $attributes['contact_email'] === 'maria@empresa.com.br'
                && $attributes['state_registration'] === '64007281-0' // IE
                && $attributes['city_registration'] === '3205309'     // IBGE município
                && $attributes['customer_group_id'] === 5             // grupo resolvido
                && ! array_key_exists('complemento', $attributes);   // vazio omitido
        })
        ->andReturnUsing(function () {
            $customer = new Customer;
            $customer->id = 10;

            return $customer;
        });
    $customers->shouldReceive('syncFinancialContacts')
        ->once()
        ->withArgs(fn (Customer $customer, array $contacts) => $customer->id === 10 && $contacts === [
            ['name' => 'Maria Comunicação', 'email' => 'maria@empresa.com.br'],
            ['name' => 'Carlos Cobrança', 'email' => 'carlos@empresa.com.br'],
        ]);

    $service->register([
        'id' => 154,
        'name' => 'ACME',
        'cnpj' => '18.587.938/0001-15',
        'telephone_1' => '2725929137',
        'logradouro' => 'Beco Almir Barbosa',
        'numero' => '685',
        'complemento' => '',
        'postal_code' => '29052-046',
        'state_id' => 32,
        'notes_2' => 'obs',
        'email' => 'contato@empresa.com.br',
        'software_id' => 1,
        'contact_name' => 'João Silva',
        'contact_email' => 'joao.silva@empresa.com.br',
        'contacts' => [
            ['name' => 'Maria Comunicação', 'email' => 'maria@empresa.com.br'],
            ['name' => 'Carlos Cobrança', 'email' => 'carlos@empresa.com.br'],
        ],
        'city_registration' => '3205309',
        'state_registration' => '64007281-0',
        'business_group' => [
            'code' => 'BFC27A6401563A',
            'name' => 'Grupo Empresarial Exemplo',
        ],
    ]);
});

it('não consulta estado quando state_id não é enviado', function () {
    $service = makeService($customers, $states, $groups);

    $customers->shouldReceive('transaction')
        ->once()
        ->andReturnUsing(static fn (\Closure $callback) => $callback());
    $states->shouldNotReceive('findIdByIbgeCode');
    $group = new CustomerGroup;
    $group->id = 5;
    $groups->shouldReceive('upsertByFinancialCode')
        ->once()
        ->with('GROUP01', 'Grupo ACME')
        ->andReturn($group);
    $customers->shouldReceive('upsertByFinanceiroId')->once()->andReturn(new Customer);
    $customers->shouldNotReceive('syncFinancialContacts');

    $service->register([
        'id' => 1,
        'name' => 'ACME',
        'cnpj' => '123',
        'business_group' => [
            'code' => 'GROUP01',
            'name' => 'Grupo ACME',
        ],
    ]);
});

it('inativa delegando ao repositório (is_active = false)', function () {
    $service = makeService($customers, $states, $groups);

    $expected = new Customer;
    $customers->shouldReceive('setActiveStatus')->once()->with(154, false)->andReturn($expected);

    expect($service->inactivate(154))->toBe($expected);
});

it('reativa delegando ao repositório (is_active = true)', function () {
    $service = makeService($customers, $states, $groups);

    $expected = new Customer;
    $customers->shouldReceive('setActiveStatus')->once()->with(154, true)->andReturn($expected);

    expect($service->reactivate(154))->toBe($expected);
});

it('lança ModelNotFoundException quando o cliente não existe', function () {
    $service = makeService($customers, $states, $groups);

    $customers->shouldReceive('setActiveStatus')->once()->with(999, false)->andReturnNull();

    $service->inactivate(999);
})->throws(ModelNotFoundException::class);
