<?php

/** Grupo Empresarial e situação contratual são controlados pelo Financeiro. */

use App\Models\Company;
use App\Models\CustomerGroup;

describe('Empresas — dados controlados pelo Financeiro', function () {

    it('não permite informar grupo empresarial no formulário de criação', function () {
        actingAsAgent();

        $this->get(route('agent.companies.manage.create'))
            ->assertOk()
            ->assertSee('Grupo Empresarial')
            ->assertSee('controlado pelo Financeiro')
            ->assertDontSee('name="customer_group_name"', false)
            ->assertDontSee('name="customer_group_id"', false)
            ->assertDontSee('Código Empresarial');
    });

    it('não expõe campos de situação controlados pelo financeiro nos formulários', function () {
        actingAsAgent();
        $company = Company::factory()->create();

        $this->get(route('agent.companies.manage.create'))
            ->assertOk()
            ->assertSee('Contrato controlado pelo Financeiro')
            ->assertDontSee('Status financeiro sincronizado')
            ->assertDontSee('Inadimplência também é definida pelo financeiro e não pode ser editada pelo suporte.')
            ->assertDontSee('name="is_active"', false)
            ->assertDontSee('name="financial_irregular"', false);

        $this->get(route('agent.companies.manage.edit', $company))
            ->assertOk()
            ->assertDontSee('Contrato controlado pelo Financeiro')
            ->assertDontSee('Somente o financeiro pode suspender, desativar ou reativar o contrato.')
            ->assertDontSee('Status financeiro sincronizado')
            ->assertDontSee('Inadimplência também é definida pelo financeiro e não pode ser editada pelo suporte.')
            ->assertDontSee('name="is_active"', false)
            ->assertDontSee('name="financial_irregular"', false);
    });

    it('exibe o grupo empresarial apenas como informação na edição', function () {
        actingAsAgent();
        $group = CustomerGroup::factory()->create([
            'name' => 'Grupo Horizonte',
            'financial_code' => 'FIN-GRUPO-01',
        ]);
        $company = Company::factory()->create(['customer_group_id' => $group->id]);

        $this->get(route('agent.companies.manage.edit', $company))
            ->assertOk()
            ->assertSee('Grupo Horizonte')
            ->assertSee('FIN-GRUPO-01')
            ->assertSee('controlado pelo Financeiro')
            ->assertDontSee('name="customer_group_name"', false)
            ->assertDontSee('name="customer_group_id"', false)
            ->assertDontSee('Código Empresarial');
    });

    it('ignora tentativa do suporte de alterar o grupo empresarial', function () {
        actingAsAgent();
        $currentGroup = CustomerGroup::factory()->create(['name' => 'Grupo Atual']);
        $otherGroup = CustomerGroup::factory()->create(['name' => 'Grupo Injetado']);
        $company = Company::factory()->create(['customer_group_id' => $currentGroup->id]);

        $this->put(route('agent.companies.manage.update', $company), [
            'name' => 'Empresa Atualizada',
            'customer_group_id' => $otherGroup->id,
            'customer_group_name' => 'Novo Grupo Injetado',
        ])->assertRedirect();

        expect($company->fresh()->customer_group_id)->toBe($currentGroup->id)
            ->and(CustomerGroup::where('name', 'Novo Grupo Injetado')->exists())->toBeFalse();
    });

    it('ignora código empresarial descontinuado e persiste os campos vigentes', function () {
        actingAsAgent();

        $this->post(route('agent.companies.manage.store'), [
            'name' => 'Empresa Teste LTDA',
            'codigo_empresarial' => 'ABC12345',
            'city_registration' => '3205309',
            'state_registration' => '123.456.789-0',
            'is_active' => 1,
        ])->assertRedirect();

        $company = Company::where('name', 'Empresa Teste LTDA')->first();

        expect($company->codigo_empresarial)->toBeNull()
            ->and($company->city_registration)->toBe('3205309')
            ->and($company->state_registration)->toBe('123.456.789-0');
    });

    it('não altera código empresarial legado pela manutenção da empresa', function () {
        actingAsAgent();
        $company = Company::factory()->create(['codigo_empresarial' => 'LEGADO']);

        $this->put(route('agent.companies.manage.update', $company), [
            'name' => $company->name,
            'codigo_empresarial' => 'XYZ999',
            'city_registration' => '3550308',
            'state_registration' => '999.888.777-6',
            'is_active' => 1,
        ])->assertRedirect();

        $company->refresh();

        expect($company->codigo_empresarial)->toBe('LEGADO')
            ->and($company->city_registration)->toBe('3550308')
            ->and($company->state_registration)->toBe('999.888.777-6');
    });

    it('rejeita código municipal fora do padrão IBGE na criação e edição', function () {
        actingAsAgent();

        $this->post(route('agent.companies.manage.store'), [
            'name' => 'Empresa Código Inválido',
            'city_registration' => '64007281-0',
        ])->assertSessionHasErrors('city_registration');

        expect(Company::query()->where('name', 'Empresa Código Inválido')->exists())->toBeFalse();

        $company = Company::factory()->create([
            'city_registration' => '3205309',
        ]);

        $this->put(route('agent.companies.manage.update', $company), [
            'name' => $company->name,
            'city_registration' => '12345',
        ])->assertSessionHasErrors('city_registration');

        expect($company->fresh()->city_registration)->toBe('3205309');
    });

    it('persiste telephone_2', function () {
        actingAsAgent();

        $this->post(route('agent.companies.manage.store'), [
            'name' => 'Empresa Fone2 LTDA',
            'phone' => '(11) 99999-9999',
            'telephone_2' => '(11) 88888-8888',
            'is_active' => 1,
        ])->assertRedirect();

        $company = Company::where('name', 'Empresa Fone2 LTDA')->first();

        expect($company->phone)->toBe('(11) 99999-9999')
            ->and($company->telephone_2)->toBe('(11) 88888-8888');
    });

    it('ignora tentativa do suporte de alterar situação contratual e financeira na edição', function () {
        actingAsAgent();
        $company = Company::factory()->create([
            'name' => 'Empresa Protegida',
            'is_active' => false,
            'financial_irregular' => true,
        ]);

        $this->put(route('agent.companies.manage.update', $company), [
            'name' => 'Empresa Protegida Atualizada',
            'is_active' => 1,
            'financial_irregular' => 0,
        ])->assertRedirect();

        $company->refresh();

        expect($company->name)->toBe('Empresa Protegida Atualizada')
            ->and((bool) $company->is_active)->toBeFalse()
            ->and((bool) $company->financial_irregular)->toBeTrue();
    });

    it('bloqueia alteração de ativo pelo suporte', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => false]);

        $this->patchJson(route('agent.companies.toggle-active', $company))
            ->assertForbidden()
            ->assertJsonPath('success', false);

        expect((bool) $company->fresh()->is_active)->toBeFalse();
    });

});
