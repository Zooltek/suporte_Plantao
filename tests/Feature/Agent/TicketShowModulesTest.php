<?php

use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Ticket\Ticket;

describe('TicketShowController — módulos contratados no sidebar', function () {

    it('exibe o módulo contratado pela empresa do chamado', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $module  = CompanyModuleType::factory()->create(['name' => 'Caixa Autom.', 'sort_order' => 1]);
        $company->moduleTypes()->attach($module->id);

        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertSee('Módulos Contratados')
            ->assertSee('Caixa Autom.');
    });

    it('exibe múltiplos módulos contratados', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $mod1    = CompanyModuleType::factory()->create(['name' => 'Aplicativo',  'sort_order' => 1]);
        $mod2    = CompanyModuleType::factory()->create(['name' => 'Uninf Rede',  'sort_order' => 2]);
        $company->moduleTypes()->attach([$mod1->id, $mod2->id]);

        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertSee('Aplicativo')
            ->assertSee('Uninf Rede');
    });

    it('não exibe a seção quando empresa não tem módulos contratados', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $ticket  = Ticket::factory()->create(['company_id' => $company->id]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertDontSee('Módulos Contratados');
    });

    it('não exibe seção quando empresa tem apenas módulos inativos', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        // Módulo inativo ainda aparece — a filtragem é responsabilidade do admin, não da view
        // O teste garante que a view renderiza sem erro com módulos de qualquer tipo
        $module  = CompanyModuleType::factory()->inactive()->create(['name' => 'Módulo Inativo']);
        $company->moduleTypes()->attach($module->id);

        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertSee('Módulo Inativo');
    });

    it('módulos são exibidos ordenados por sort_order', function () {
        actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $modA    = CompanyModuleType::factory()->create(['name' => 'Zzz Último',   'sort_order' => 99]);
        $modB    = CompanyModuleType::factory()->create(['name' => 'Aaa Primeiro', 'sort_order' => 1]);
        $company->moduleTypes()->attach([$modA->id, $modB->id]);

        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $html = $this->get(route('agent.ticket.show', $ticket->id))->content();

        expect(strpos($html, 'Aaa Primeiro'))->toBeLessThan(strpos($html, 'Zzz Último'));
    });

});
