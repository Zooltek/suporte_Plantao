<?php

use App\Models\Company;
use App\Models\CompanyModuleType;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Services\Agent\TicketShowService;
use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;

describe('TicketShowService — loadForDisplay', function () {

    it('carrega a relação company.moduleTypes no ticket', function () {
        $ticket = Ticket::factory()->create();

        $loaded = app(TicketShowService::class)->loadForDisplay($ticket);

        expect($loaded->relationLoaded('company'))->toBeTrue()
            ->and($loaded->company->relationLoaded('moduleTypes'))->toBeTrue();
    });

    it('módulos contratados da empresa ficam acessíveis após o load', function () {
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $mod1 = CompanyModuleType::factory()->create(['sort_order' => 1]);
        $mod2 = CompanyModuleType::factory()->create(['sort_order' => 2]);
        $company->moduleTypes()->attach([$mod1->id, $mod2->id]);

        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $loaded = app(TicketShowService::class)->loadForDisplay($ticket);
        $modIds = $loaded->company->moduleTypes->pluck('id')->sort()->values();

        expect($modIds)->toEqual(collect([$mod1->id, $mod2->id])->sort()->values());
    });

    it('ticket com empresa sem módulos retorna collection vazia sem exceção', function () {
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $ticket = Ticket::factory()->create(['company_id' => $company->id]);

        $loaded = app(TicketShowService::class)->loadForDisplay($ticket);

        expect($loaded->company->moduleTypes)->toBeEmpty();
    });

    it('carrega também as demais relações exigidas pela view', function () {
        $ticket = Ticket::factory()->create();
        $loaded = app(TicketShowService::class)->loadForDisplay($ticket);

        foreach (['status', 'category', 'agent', 'author', 'company'] as $relation) {
            expect($loaded->relationLoaded($relation))->toBeTrue("relação '$relation' não carregada");
        }
    });

    it('separa status rápidos dos status de encerramento no contexto da view', function () {
        $this->seed(StatusSeeder::class);
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $context = app(TicketShowService::class)->getContextData(actingAsAdmin());

        expect($context['quickStatuses']->contains(fn ($status) => $status->id === $terminal->id))->toBeFalse()
            ->and($context['closeStatuses']->contains(fn ($status) => $status->id === $terminal->id))->toBeTrue();
    });

});
