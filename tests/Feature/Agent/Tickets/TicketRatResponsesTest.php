<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;

/**
 * Cobre o RAT dinâmico: persistência de rat_responses no chamado.
 */
describe('TicketStore — RAT responses persistência', function () {

    function rat_payload(int $agentId): array
    {
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        $child = Category::factory()->create(['parent_id' => $parent->category_id, 'priority' => 'low']);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);

        return [
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'sub_category_id' => $child->category_id,
            'contact' => 'JOAO TESTE',
            'agent_id' => $agentId,
            'trouble' => 'Problema no módulo',
        ];
    }

    it('salva rat_responses como JSON quando enviado', function () {
        $agent = actingAsAgent();

        $payload = rat_payload($agent->id);
        $payload['rat_responses'] = [
            '10' => '1',
            '11' => 'Configurado com sucesso',
        ];

        $this->post(route('agent.ticket.store'), $payload)
            ->assertRedirect();

        $ticket = Ticket::latest('id')->first();
        expect($ticket->rat_responses)->toBeArray()
            ->and($ticket->rat_responses['10'])->toBe('1')
            ->and($ticket->rat_responses['11'])->toBe('Configurado com sucesso');
    });

    it('salva rat_responses como null quando não enviado', function () {
        actingAsAgent();
        $agent = \App\Models\Ticket\Agent::where('ticketit_agent', 1)->first();

        $payload = rat_payload($agent->id);
        // sem rat_responses no payload

        $this->post(route('agent.ticket.store'), $payload)
            ->assertRedirect();

        $ticket = Ticket::latest('id')->first();
        expect($ticket->rat_responses)->toBeNull();
    });

    it('view de criação injeta urlRatElements no window.__ticketFormConfig', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertSee('urlRatElements');
    });

    it('view de criação injeta ratResponses existentes ao editar', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        $child = Category::factory()->create(['parent_id' => $parent->category_id, 'priority' => 'low']);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);

        $ticket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'company_id' => $company->id,
            'category_id' => $parent->category_id,
            'status_id' => $status->id,
            'rat_responses' => ['5' => '1', '6' => 'OK'],
        ]);

        $this->get(route('agent.ticket.edit', $ticket))
            ->assertOk()
            ->assertSee('ratResponses');
    });
});
