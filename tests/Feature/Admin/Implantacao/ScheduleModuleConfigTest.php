<?php

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function smc_company(array $attrs = []): Company
{
    return Company::factory()->create(array_merge(['is_active' => true, 'financial_irregular' => false], $attrs));
}

function smc_module(array $attrs = []): Module
{
    return Module::factory()->create($attrs);
}

// ─── Admin: index ─────────────────────────────────────────────────────────────

describe('Admin ScheduleModule — index', function () {

    it('admin acessa lista de módulos por cliente', function () {
        actingAsAdmin();

        $this->get(route('admin.implantacao.modules.index'))
            ->assertOk()
            ->assertViewIs('admin.implantacao.modules.index');
    });

    it('exibe badge "Todos (padrão)" para cliente sem módulos configurados', function () {
        actingAsAdmin();
        $company = smc_company(['trade_name' => 'Empresa Sem Módulos']);

        $this->get(route('admin.implantacao.modules.index'))
            ->assertOk()
            ->assertSee('Todos (padrão)');
    });

    it('exibe módulos configurados para cliente que os tem', function () {
        actingAsAdmin();
        $company = smc_company(['trade_name' => 'Empresa Com Módulos']);
        $module  = smc_module(['name' => 'ERP-TESTE']);

        $company->scheduleModules()->sync([$module->id]);

        $this->get(route('admin.implantacao.modules.index'))
            ->assertOk()
            ->assertSee('ERP-TESTE');
    });

});

// ─── Admin: edit ──────────────────────────────────────────────────────────────

describe('Admin ScheduleModule — edit', function () {

    it('admin acessa form de edição de módulos para um cliente', function () {
        actingAsAdmin();
        $company = smc_company();

        $this->get(route('admin.implantacao.modules.edit', $company->id))
            ->assertOk()
            ->assertViewIs('admin.implantacao.modules.edit')
            ->assertViewHas('company')
            ->assertViewHas('allModules')
            ->assertViewHas('assigned');
    });

    it('módulos já configurados aparecem pré-selecionados no form', function () {
        actingAsAdmin();
        $company = smc_company();
        $module  = smc_module(['name' => 'NF-TESTE']);
        $company->scheduleModules()->sync([$module->id]);

        $response = $this->get(route('admin.implantacao.modules.edit', $company->id));
        $assigned = $response->viewData('assigned');

        expect($assigned)->toContain($module->id);
    });

});

// ─── Admin: update ────────────────────────────────────────────────────────────

describe('Admin ScheduleModule — update', function () {

    it('admin salva módulos para um cliente', function () {
        actingAsAdmin();
        $company = smc_company();
        $m1 = smc_module();
        $m2 = smc_module();

        $this->put(route('admin.implantacao.modules.update', $company->id), [
            'module_ids' => [$m1->id, $m2->id],
        ])->assertRedirect(route('admin.implantacao.modules.index'))
          ->assertSessionHas('success');

        $this->assertDatabaseHas('customer_schedule_module', [
            'customer_id'        => $company->id,
            'schedule_module_id' => $m1->id,
        ]);
        $this->assertDatabaseHas('customer_schedule_module', [
            'customer_id'        => $company->id,
            'schedule_module_id' => $m2->id,
        ]);
    });

    it('salvar sem módulos limpa configuração (volta ao padrão)', function () {
        actingAsAdmin();
        $company = smc_company();
        $module  = smc_module();
        $company->scheduleModules()->sync([$module->id]);

        $this->put(route('admin.implantacao.modules.update', $company->id), [])
            ->assertRedirect(route('admin.implantacao.modules.index'));

        $this->assertDatabaseMissing('customer_schedule_module', [
            'customer_id' => $company->id,
        ]);
    });

    it('ID inválido de módulo falha com 422', function () {
        actingAsAdmin();
        $company = smc_company();

        $this->put(route('admin.implantacao.modules.update', $company->id), [
            'module_ids' => [99999],
        ])->assertSessionHasErrors('module_ids.0');
    });

});

// ─── API: company schedule-modules ────────────────────────────────────────────

describe('API GET /api/v1/companies/{company}/schedule-modules', function () {

    it('retorna todos os módulos quando cliente não tem configuração (fallback)', function () {
        actingAsAgent();
        $company = smc_company();
        $m1 = smc_module(['name' => 'ERP-API', 'project' => 'Geral']);
        $m2 = smc_module(['name' => 'NFE-API', 'project' => 'Fiscal']);

        $response = $this->getJson("/api/v1/companies/{$company->id}/schedule-modules");

        $response->assertOk();
        $ids = collect($response->json())->flatten(1)->pluck('id');
        expect($ids)->toContain($m1->id)
                    ->toContain($m2->id);
    });

    it('retorna apenas módulos configurados quando cliente tem seleção', function () {
        actingAsAgent();
        $company = smc_company();
        $m1 = smc_module(['name' => 'ERP-FILTRO', 'project' => 'Geral']);
        $m2 = smc_module(['name' => 'NFE-FILTRO', 'project' => 'Fiscal']);

        $company->scheduleModules()->sync([$m1->id]); // só m1

        $response = $this->getJson("/api/v1/companies/{$company->id}/schedule-modules");

        $response->assertOk();
        $ids = collect($response->json())->flatten(1)->pluck('id');
        expect($ids)->toContain($m1->id)
                    ->not->toContain($m2->id);
    });

    it('resposta é agrupada por projeto', function () {
        actingAsAgent();
        $company = smc_company();
        $module  = smc_module(['name' => 'ERP-GRP', 'project' => 'MeuProjeto']);
        $company->scheduleModules()->sync([$module->id]);

        $response = $this->getJson("/api/v1/companies/{$company->id}/schedule-modules");

        $response->assertOk()
            ->assertJsonStructure(['MeuProjeto' => [['id', 'name', 'project']]]);
    });

    it('retorna 404 para cliente inexistente', function () {
        actingAsAgent();

        $this->getJson('/api/v1/companies/99999/schedule-modules')
            ->assertNotFound();
    });

});

// ─── Service: ScheduleModuleConfigService ────────────────────────────────────

describe('ScheduleModuleConfigService', function () {

    it('getForCompany retorna todos os módulos quando nenhum configurado', function () {
        $company = smc_company();
        $m1 = smc_module();
        $m2 = smc_module();

        $service = app(ScheduleModuleConfigService::class);
        $result  = $service->getForCompany($company);

        expect($result->pluck('id'))->toContain($m1->id)
                                    ->toContain($m2->id);
    });

    it('getForCompany retorna apenas módulos configurados', function () {
        $company = smc_company();
        $m1 = smc_module();
        $m2 = smc_module();
        $company->scheduleModules()->sync([$m1->id]);

        $service = app(ScheduleModuleConfigService::class);
        $result  = $service->getForCompany($company->fresh());

        expect($result->pluck('id'))->toContain($m1->id)
                                    ->not->toContain($m2->id);
    });

    it('syncForCompany persiste a seleção no pivot', function () {
        $company = smc_company();
        $module  = smc_module();

        $service = app(ScheduleModuleConfigService::class);
        $service->syncForCompany($company, [$module->id]);

        expect($company->fresh()->scheduleModules->pluck('id'))->toContain($module->id);
    });

    it('syncForCompany com array vazio remove todos os vínculos', function () {
        $company = smc_company();
        $module  = smc_module();
        $company->scheduleModules()->sync([$module->id]);

        $service = app(ScheduleModuleConfigService::class);
        $service->syncForCompany($company, []);

        expect($company->fresh()->scheduleModules)->toBeEmpty();
    });

});
