<?php

use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\User;

describe('Condensed — link da Fila de Pendências para abrir o chamado', function () {

    it('renderiza link clicável para o chamado pendente sem responsável', function () {
        actingAsAdmin();

        $company = Company::factory()->create(['trade_name' => 'Cliente Sem Agente']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id'  => Ticket::STATUS_PENDING_ID,
            'agent_id'   => null,
            'completed_at' => null,
        ]);

        $expectedHref = route('agent.ticket.show', $ticket->id);

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertSee("href=\"{$expectedHref}\"", false)
            ->assertSee("#{$ticket->id}");
    });

    it('renderiza link clicável também para chamado já com responsável', function () {
        $admin = actingAsAdmin();

        $company = Company::factory()->create(['trade_name' => 'Cliente Atribuido']);
        $agent   = User::factory()->agent()->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id'  => Ticket::STATUS_PENDING_ID,
            'agent_id'   => $agent->id,
            'completed_at' => null,
        ]);

        $expectedHref = route('agent.ticket.show', $ticket->id);

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertSee("href=\"{$expectedHref}\"", false);
    });

    it('não usa mais a função fantasma popupChamado', function () {
        actingAsAdmin();

        Ticket::factory()->create([
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id'  => null,
        ]);

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertDontSee('popupChamado');
    });

});
