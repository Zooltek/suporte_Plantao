<?php

use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Facades\DB;

/**
 * Testa o comportamento do botão "Descartar e Voltar" no formulário de chamado.
 *
 * Fluxo do usuário:
 * - Ao criar um novo chamado e clicar em "Descartar" → deve ver a tela principal (agent.index)
 * - Ao editar um chamado existente e clicar em "Descartar" → deve ver a tela do chamado (agent.ticket.show)
 */
describe('Botão "Descartar e Voltar" — navegação contextual', function () {

    beforeEach(function () {
        DB::table('ticketit_statuses')
            ->where('id', 2)
            ->update([
                'name'              => 'Em andamento',
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent'    => true,
                'is_terminal'       => false,
            ]);
    });

    it('formulário de CRIAÇÃO contém link para agent.index no botão Descartar', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertSee(route('agent.index'), false);
    });

    it('formulário de CRIAÇÃO NÃO contém javascript:history.back()', function () {
        actingAsAgent();

        $this->get(route('agent.ticket.create'))
            ->assertOk()
            ->assertDontSee('javascript:history.back()', false);
    });

    it('formulário de EDIÇÃO contém link para agent.ticket.show no botão Descartar', function () {
        $agent   = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status  = Status::factory()->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id'   => $agent->id,
            'status_id'  => $status->id,
            'user_id'    => $agent->id,
            'author_id'  => $agent->id,
        ]);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertOk()
            ->assertSee(route('agent.ticket.show', $ticket->id), false);
    });

    it('formulário de EDIÇÃO NÃO contém javascript:history.back()', function () {
        $agent   = actingAsAgent();
        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status  = Status::factory()->create();

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'agent_id'   => $agent->id,
            'status_id'  => $status->id,
            'user_id'    => $agent->id,
            'author_id'  => $agent->id,
        ]);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertOk()
            ->assertDontSee('javascript:history.back()', false);
    });

});
