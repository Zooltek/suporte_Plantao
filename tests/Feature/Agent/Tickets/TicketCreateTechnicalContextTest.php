<?php

use App\Models\Company;
use App\Models\Software;
use App\Models\Ticket\Ticket;

describe('TicketCreate — contexto técnico por cliente', function () {

    it('view de criação carrega software junto das empresas', function () {
        actingAsAgent();

        $software = Software::factory()->create([
            'name' => 'EasyMaster',
            'version' => '01.32.01',
        ]);

        $company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'software_id' => $software->id,
        ]);

        $companies = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companies');

        $target = $companies->find($company->id);

        expect($target->relationLoaded('software'))->toBeTrue()
            ->and($target->software?->name)->toBe('EasyMaster')
            ->and($target->software?->version)->toBe('01.32.01');
    });

    it('injeta contexto técnico isolado por empresa na view', function () {
        actingAsAgent();

        $softwareA = Software::factory()->create(['name' => 'EasyMaster', 'version' => '01.32.01']);
        $softwareB = Software::factory()->create(['name' => 'AmuraWeb', 'version' => '03.05.00']);

        $companyA = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'software_id' => $softwareA->id,
        ]);

        $companyB = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'software_id' => $softwareB->id,
        ]);

        Ticket::factory()->create([
            'company_id' => $companyA->id,
            'version' => '01.30.01',
            'release' => 'R10',
            'created_at' => now()->subHour(),
        ]);

        Ticket::factory()->create([
            'company_id' => $companyB->id,
            'version' => '03.05.00',
            'release' => 'WEB-22',
            'created_at' => now(),
        ]);

        $technicalContexts = $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->viewData('companyTechnicalContexts');

        expect($technicalContexts[(string) $companyA->id]['suggested_version'])->toBe('01.30.01')
            ->and($technicalContexts[(string) $companyA->id]['suggested_release'])->toBe('R10')
            ->and($technicalContexts[(string) $companyA->id]['software_version'])->toBe('01.32.01')
            ->and($technicalContexts[(string) $companyB->id]['suggested_version'])->toBe('03.05.00')
            ->and($technicalContexts[(string) $companyB->id]['suggested_release'])->toBe('WEB-22')
            ->and($technicalContexts[(string) $companyB->id]['software_name'])->toBe('AmuraWeb');
    });

    it('catálogo de versões vem do backend e a view não usa localStorage global', function () {
        actingAsAgent();

        $software = Software::factory()->create([
            'name' => 'Plenus',
            'version' => '04.01.00',
        ]);

        Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
            'software_id' => $software->id,
        ]);

        $response = $this->get(route('agent.ticket.create'))
            ->assertOk();

        $catalog = $response->viewData('ticketVersionCatalog');

        expect($catalog)->toContain('01.28.01')
            ->and($catalog)->toContain('04.01.00');

        $response->assertDontSee('ticket_last_release')
            ->assertDontSee('ticket_last_version');
    });

});
