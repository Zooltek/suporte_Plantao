<?php

use App\Models\CompanyModuleType;
use App\Models\Schedule\Module as RatModule;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function cmt_rat(string $name = 'RAT Teste'): RatModule
{
    return RatModule::firstOrCreate(['name' => $name], ['project' => 'EasyMaster']);
}

// ─── Listagem ─────────────────────────────────────────────────────────────────

describe('CompanyModule — listagem', function () {

    it('admin vê a lista de módulos', function () {
        actingAsAdmin();
        CompanyModuleType::factory()->count(2)->create();

        $this->get(route('admin.helpdesk.modules.index'))
            ->assertStatus(200)
            ->assertSee(CompanyModuleType::orderBy('sort_order')->first()->name);
    });

    it('lista exibe o status do checklist RAT e quantidade de clientes impactados', function () {
        actingAsAdmin();
        $rat = cmt_rat('RAT Lista');

        $linked = CompanyModuleType::factory()->withRatModule($rat)->create(['name' => 'Módulo Vinculado']);
        $unlinked = CompanyModuleType::factory()->create(['name' => 'Módulo Sem RAT']);

        $companyA = \App\Models\Company::factory()->create();
        $companyB = \App\Models\Company::factory()->create();

        $linked->companies()->attach($companyA->id);
        $unlinked->companies()->attach($companyA->id);
        $unlinked->companies()->attach($companyB->id);

        $this->get(route('admin.helpdesk.modules.index'))
            ->assertOk()
            ->assertSee('RAT Lista')
            ->assertSee('Sem checklist padrão')
            ->assertSee('Com impacto');
    });

    it('não autenticado é redirecionado da lista de módulos', function () {
        $this->get(route('admin.helpdesk.modules.index'))
            ->assertRedirect();
    });

});

// ─── Criar ────────────────────────────────────────────────────────────────────

describe('CompanyModule — criar', function () {

    it('exibe formulário de criação com select de RAT modules', function () {
        actingAsAdmin();
        $rat = cmt_rat();

        $this->get(route('admin.helpdesk.modules.create'))
            ->assertStatus(200)
            ->assertSee($rat->name);
    });

    it('admin cria módulo com dados válidos', function () {
        actingAsAdmin();

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => 'Fiscal',
            'sort_order' => 1,
            'is_active' => '1',
        ])->assertRedirect(route('admin.helpdesk.modules.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('company_module_types', ['name' => 'Fiscal']);
    });

    it('admin cria módulo sem sort_order (campo opcional)', function () {
        actingAsAdmin();

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => 'Contábil',
            'is_active' => '1',
        ])->assertRedirect(route('admin.helpdesk.modules.index'));

        $this->assertDatabaseHas('company_module_types', ['name' => 'Contábil']);
    });

    it('admin vincula rat_module_id ao criar módulo', function () {
        actingAsAdmin();
        $rat = cmt_rat('RAT Criação');

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => 'Módulo RAT',
            'is_active' => '1',
            'rat_module_id' => $rat->id,
        ])->assertRedirect(route('admin.helpdesk.modules.index'));

        $this->assertDatabaseHas('company_module_types', [
            'name' => 'Módulo RAT',
            'rat_module_id' => $rat->id,
        ]);
    });

    it('rejeita rat_module_id inexistente', function () {
        actingAsAdmin();

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => 'Inválido',
            'is_active' => '1',
            'rat_module_id' => 999999,
        ])->assertSessionHasErrors('rat_module_id');
    });

    it('rejeita módulo sem nome', function () {
        actingAsAdmin();

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => '',
            'is_active' => '1',
        ])->assertSessionHasErrors('name');
    });

    it('rejeita sort_order não numérico', function () {
        actingAsAdmin();

        $this->post(route('admin.helpdesk.modules.store'), [
            'name' => 'Folha',
            'sort_order' => 'abc',
            'is_active' => '1',
        ])->assertSessionHasErrors('sort_order');
    });

});

// ─── Editar / Atualizar ───────────────────────────────────────────────────────

describe('CompanyModule — editar e atualizar', function () {

    it('exibe formulário de edição com select de RAT modules', function () {
        actingAsAdmin();
        $rat = cmt_rat('RAT Edição');
        $module = CompanyModuleType::factory()->withRatModule($rat)->create();

        $this->get(route('admin.helpdesk.modules.edit', $module))
            ->assertStatus(200)
            ->assertSee($module->name)
            ->assertSee($rat->name);
    });

    it('avisa quando módulo com clientes vinculados está sem checklist padrão', function () {
        actingAsAdmin();
        $module = CompanyModuleType::factory()->create();
        $company = \App\Models\Company::factory()->create();
        $module->companies()->attach($company->id);

        $this->get(route('admin.helpdesk.modules.edit', $module))
            ->assertOk()
            ->assertSee('não possui checklist RAT padrão');
    });

    it('admin atualiza módulo com dados válidos', function () {
        actingAsAdmin();
        $module = CompanyModuleType::factory()->create(['name' => 'Antigo Módulo']);

        $this->put(route('admin.helpdesk.modules.update', $module), [
            'name' => 'Módulo Atualizado',
            'sort_order' => 5,
            'is_active' => '1',
        ])->assertRedirect(route('admin.helpdesk.modules.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('company_module_types', ['name' => 'Módulo Atualizado']);
        $this->assertDatabaseMissing('company_module_types', ['name' => 'Antigo Módulo']);
    });

    it('admin vincula rat_module_id ao atualizar módulo', function () {
        actingAsAdmin();
        $rat = cmt_rat('RAT Update');
        $module = CompanyModuleType::factory()->create();

        $this->put(route('admin.helpdesk.modules.update', $module), [
            'name' => $module->name,
            'is_active' => '1',
            'rat_module_id' => $rat->id,
        ])->assertRedirect(route('admin.helpdesk.modules.index'));

        $this->assertDatabaseHas('company_module_types', [
            'id' => $module->id,
            'rat_module_id' => $rat->id,
        ]);
    });

    it('admin remove vínculo RAT ao atualizar módulo sem rat_module_id', function () {
        actingAsAdmin();
        $rat = cmt_rat('RAT Remover');
        $module = CompanyModuleType::factory()->withRatModule($rat)->create();

        $this->put(route('admin.helpdesk.modules.update', $module), [
            'name' => $module->name,
            'is_active' => '1',
            // rat_module_id não enviado → deve limpar o vínculo
        ])->assertRedirect(route('admin.helpdesk.modules.index'));

        $this->assertDatabaseHas('company_module_types', [
            'id' => $module->id,
            'rat_module_id' => null,
        ]);
    });

    it('retorna 404 ao editar módulo inexistente', function () {
        actingAsAdmin();

        $this->get(route('admin.helpdesk.modules.edit', 9999))
            ->assertStatus(404);
    });

});

// ─── Excluir ──────────────────────────────────────────────────────────────────

describe('CompanyModule — excluir', function () {

    it('admin remove módulo existente', function () {
        actingAsAdmin();
        $module = CompanyModuleType::factory()->create();

        $this->delete(route('admin.helpdesk.modules.destroy', $module))
            ->assertRedirect(route('admin.helpdesk.modules.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('company_module_types', ['id' => $module->id]);
    });

});
