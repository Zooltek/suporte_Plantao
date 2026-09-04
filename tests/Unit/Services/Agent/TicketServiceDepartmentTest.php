<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Agent\TicketService;

function tsd_cat_pair_with_dept(?int $deptId): array
{
    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $deptId]);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    return [$cat, $sub];
}

function tsd_minimal_data(Company $company, Category $cat, Category $sub, array $override = []): array
{
    return array_merge([
        'company_id' => $company->id,
        'status_id' => Ticket::STATUS_PENDING_ID,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
        'contact' => 'TESTE',
        'agent_id' => null,
        'is_recurring' => false,
    ], $override);
}

describe('TicketService — department_id via Resolver', function () {

    it('usa department da categoria quando há override ausente e agente sem departamento', function () {
        $comercial = Department::factory()->create(['name' => 'Comercial Phase2']);
        $agent = User::factory()->agent()->create(['department_id' => null]);
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = tsd_cat_pair_with_dept($comercial->id);

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            tsd_minimal_data($company, $cat, $sub, ['agent_id' => $agent->id]),
            $agent,
        );

        expect($ticket->department_id)->toBe($comercial->id);
    });

    it('departamento da categoria vence o departamento do agente', function () {
        $comercial = Department::factory()->create(['name' => 'Comercial Win']);
        $suporte = Department::factory()->create(['name' => 'Suporte do Agente']);
        $agent = User::factory()->agent()->create(['department_id' => $suporte->id]);
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = tsd_cat_pair_with_dept($comercial->id);

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            tsd_minimal_data($company, $cat, $sub, ['agent_id' => $agent->id]),
            $agent,
        );

        expect($ticket->department_id)->toBe($comercial->id);
    });

    it('override manual no formulário vence categoria e agente', function () {
        $explicit = Department::factory()->create(['name' => 'Override Dept']);
        $comercial = Department::factory()->create(['name' => 'Comercial Loser']);
        $agent = User::factory()->agent()->create([
            'department_id' => Department::factory()->create(['name' => 'Agent Dept'])->id,
        ]);
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = tsd_cat_pair_with_dept($comercial->id);

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            tsd_minimal_data($company, $cat, $sub, [
                'agent_id' => $agent->id,
                'department_id' => $explicit->id,
            ]),
            $agent,
        );

        expect($ticket->department_id)->toBe($explicit->id);
    });

    it('quickUpdateAgent preserva o departamento da categoria ao capturar', function () {
        $comercial = Department::factory()->create(['name' => 'Comercial Preserved']);
        $suporteAgent = Department::factory()->create(['name' => 'Suporte Agent QU']);
        $agent = User::factory()->agent()->create(['department_id' => $suporteAgent->id]);
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        [$cat, $sub] = tsd_cat_pair_with_dept($comercial->id);

        $this->actingAs($agent);

        $ticket = app(TicketService::class)->saveTicket(
            new Ticket,
            tsd_minimal_data($company, $cat, $sub),
            $agent,
        );

        expect($ticket->department_id)->toBe($comercial->id);

        app(TicketService::class)->quickUpdateAgent($ticket->refresh(), $agent->id);

        expect($ticket->refresh()->department_id)->toBe($comercial->id)
            ->and($ticket->agent_id)->toBe($agent->id);
    });

});
