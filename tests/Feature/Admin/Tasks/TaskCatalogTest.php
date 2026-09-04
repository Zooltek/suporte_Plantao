<?php

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\Tasks\Label;

describe('Admin Tasks — catálogo por cliente', function () {

    it('retorna catálogo alinhado com implantação/RAT para o cliente selecionado', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowedModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $blockedModule = Module::factory()->create(['name' => 'Fiscal RAT', 'project' => 'Fiscal']);
        $legacyRoot = Label::factory()->create(['name' => 'Financeiro RAT']);
        $legacyChild = Label::factory()->childOf($legacyRoot->id)->create(['name' => 'Contas a Receber']);

        $company->scheduleModules()->sync([$allowedModule->id]);

        $response = $this->getJson(route('tasks.catalog', ['customer_id' => $company->id]));

        $response->assertOk()
            ->assertJsonCount(1, 'projects')
            ->assertJsonPath('projects.0.name', 'ERP');

        $modules = collect($response->json('projectModuleTree'))
            ->flatten(1);

        expect($modules->pluck('name')->all())->toContain('Financeiro RAT')
            ->not->toContain('Fiscal RAT');

        $financeModule = $modules->firstWhere('name', 'Financeiro RAT');

        expect($financeModule['childs'])->toContain([
            'id' => (string) $legacyChild->id,
            'name' => 'Contas a Receber',
        ]);
    });

    it('sem cliente informado expõe o catálogo global de implantação/RAT', function () {
        actingAsAdmin();
        Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        Module::factory()->create(['name' => 'Fiscal RAT', 'project' => 'Fiscal']);

        $response = $this->getJson(route('tasks.catalog'));

        $response->assertOk()
            ->assertJsonCount(2, 'projects');

        $projectNames = collect($response->json('projects'))->pluck('name')->all();

        expect($projectNames)->toContain('ERP')
            ->toContain('Fiscal');
    });

    it('retorna os RATs recentes do cliente selecionado junto com o catálogo técnico', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $module = Module::factory()->create(['name' => 'Contábil', 'project' => 'EasyMaster']);

        $company->scheduleModules()->sync([$module->id]);

        $record = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $module->id,
            'status' => 1,
            'release' => '2026.03',
            'version' => '12.4.1',
        ]);

        Record::factory()->create([
            'customer_id' => $otherCompany->id,
            'module_id' => $module->id,
            'status' => 1,
        ]);

        $response = $this->getJson(route('tasks.catalog', ['customer_id' => $company->id]));

        $response->assertOk()
            ->assertJsonCount(1, 'ratRecords')
            ->assertJsonPath('ratRecords.0.id', (string) $record->id)
            ->assertJsonPath('ratRecords.0.moduleName', 'Contábil')
            ->assertJsonPath('ratRecords.0.projectName', 'EasyMaster')
            ->assertJsonPath('ratRecords.0.statusLabel', 'Ativo')
            ->assertJsonPath('ratRecords.0.release', '2026.03')
            ->assertJsonPath('ratRecords.0.version', '12.4.1');
    });
});
