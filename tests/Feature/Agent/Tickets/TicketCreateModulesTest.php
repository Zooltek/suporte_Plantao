<?php

use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Schedule\Module as RatModule;

/**
 * Cobre o Bug 3: módulos contratados ocultos na tela de chamados.
 *
 * Raiz: getCommonFormData() não carregava moduleTypes e $companiesData
 * não exportava os dados para o Alpine, tornando o seletor de módulos
 * inoperante mesmo quando a empresa possuía módulos cadastrados.
 */
describe('TicketCreate — módulos contratados no formulário (Bug 3)', function () {

    it('view de criação carrega moduleTypes via eager load', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $module = CompanyModuleType::factory()->create(['name' => 'TEF Online']);
        $company->moduleTypes()->attach($module->id);

        $companies = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companies');

        $target = $companies->find($company->id);

        expect($target->relationLoaded('moduleTypes'))->toBeTrue()
            ->and($target->moduleTypes->pluck('name')->toArray())->toContain('TEF Online');
    });

    it('JSON injetado na view inclui a chave module_types por empresa', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $ratModule = RatModule::factory()->create(['name' => 'Financeiro Ticket', 'project' => 'ERP']);
        $module = CompanyModuleType::factory()->withRatModule($ratModule)->create(['name' => 'CRM Integrado']);
        $company->moduleTypes()->attach($module->id);

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertSee('module_types')  // chave presente no JSON de configuração Alpine
            ->assertSee('rat_template_id')
            ->assertSee('schedule_rat_modules')
            ->assertSee('module_id');    // input hidden / select existe na view
    });

    it('módulos de empresas diferentes não se misturam no payload', function () {
        actingAsAgent();

        $empresaA = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $empresaB = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

        $modA = CompanyModuleType::factory()->create(['name' => 'Módulo Exclusivo A']);
        $modB = CompanyModuleType::factory()->create(['name' => 'Módulo Exclusivo B']);

        $empresaA->moduleTypes()->attach($modA->id);
        $empresaB->moduleTypes()->attach($modB->id);

        $companies = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companies');

        $targA = $companies->find($empresaA->id);
        $targB = $companies->find($empresaB->id);

        expect($targA->moduleTypes->pluck('name')->toArray())->toContain('Módulo Exclusivo A')
            ->and($targA->moduleTypes->pluck('id')->toArray())->not->toContain($modB->id);

        expect($targB->moduleTypes->pluck('name')->toArray())->toContain('Módulo Exclusivo B')
            ->and($targB->moduleTypes->pluck('id')->toArray())->not->toContain($modA->id);
    });

    it('view renderiza sem erro quando nenhuma empresa tem módulos', function () {
        actingAsAgent();
        Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

        $this->get(route('agent.ticket.create'))->assertOk();
    });

    it('empresa sem módulos tem module_types vazio no payload', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

        $companies = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companies');

        $target = $companies->find($company->id);

        expect($target->relationLoaded('moduleTypes'))->toBeTrue()
            ->and($target->moduleTypes)->toBeEmpty();
    });

});
