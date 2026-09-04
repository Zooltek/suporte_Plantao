<?php

use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;

beforeEach(function () {
    $this->seed(StatusSeeder::class);
});

function rtt_makeTicket(int $agentId, int $statusId = Ticket::STATUS_IN_PROGRESS_ID): Ticket
{
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);

    return Ticket::factory()->create([
        'company_id' => $company->id,
        'agent_id' => $agentId,
        'status_id' => $statusId,
        'completed_at' => null,
    ]);
}

describe('Agent — devolver chamado para a fila aberta', function () {

    it('agente responsável devolve o chamado e retorna ao status Pendente sem responsável', function () {
        $agent = actingAsAgent();
        $ticket = rtt_makeTicket($agent->id);

        $response = $this->post(route('agent.ticket.release', $ticket));

        $response->assertRedirect(route('agent.calendar.condensed', ['active' => 'pending']));
        $response->assertSessionHas('status', fn ($v) => str_contains($v, 'devolvido à fila'));

        $ticket->refresh();
        expect($ticket->agent_id)->toBeNull()
            ->and((int) $ticket->status_id)->toBe(Ticket::STATUS_PENDING_ID)
            ->and($ticket->completed_at)->toBeNull();
    });

    it('admin também pode devolver o chamado de outro agente', function () {
        $owner = actingAsAgent();
        $ticket = rtt_makeTicket($owner->id);

        actingAsAdmin();

        $this->post(route('agent.ticket.release', $ticket))
            ->assertRedirect(route('agent.calendar.condensed', ['active' => 'pending']));

        expect($ticket->fresh()->agent_id)->toBeNull();
    });

    it('agente que não é responsável e não é admin recebe 403', function () {
        $owner = \App\Models\User::factory()->agent()->create();
        $ticket = rtt_makeTicket($owner->id);

        actingAsAgent();

        $this->post(route('agent.ticket.release', $ticket))->assertForbidden();

        expect($ticket->fresh()->agent_id)->toBe($owner->id);
    });

    it('chamado em status terminal não pode ser devolvido', function () {
        $agent = actingAsAgent();
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $ticket = rtt_makeTicket($agent->id, $terminal->id);

        $this->post(route('agent.ticket.release', $ticket))
            ->assertSessionHas('warning', fn ($v) => str_contains($v, 'não podem ser devolvidos'));

        expect($ticket->fresh()->agent_id)->toBe($agent->id);
    });

    it('chamado já sem responsável retorna aviso e não é processado', function () {
        $admin = actingAsAdmin();
        $ticket = rtt_makeTicket($admin->id);
        $ticket->update(['agent_id' => null]);

        $this->post(route('agent.ticket.release', $ticket))
            ->assertSessionHas('warning', fn ($v) => str_contains($v, 'já está sem responsável'));
    });

    it('renderiza o botão "Devolver para a fila" para o agente responsável e o esconde no fluxo terminal', function () {
        $agent = actingAsAgent();
        $ticket = rtt_makeTicket($agent->id);

        $this->get(route('agent.ticket.show', $ticket))
            ->assertOk()
            ->assertSee('Devolver para a fila')
            ->assertSee('Encerrar chamado');
    });

});
