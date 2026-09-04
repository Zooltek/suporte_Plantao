<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

/**
 * Garante que a TicketPolicy bloqueia acesso a chamados terminais antigos para
 * usuários comuns e que o e-mail atendente especial continua com acesso.
 *
 * Cobre a lógica migrada de TicketsController::authorizeTicketAccess()
 * para TicketPolicy::view() e TicketPolicy::update().
 */
describe('TicketPolicy — chamados terminais antigos', function () {

    beforeEach(function () {
        $this->company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $this->parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    });

    /**
     * Cria um ticket terminal encerrado há 2 dias (completed_at < today).
     */
    function makeOldClosedTicket(Company $company, Category $parent, User $owner, ?Status $status = null): Ticket
    {
        $status ??= Status::factory()->terminal()->create([
            'name' => 'Resolvido',
        ]);

        return Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'completed_at' => now()->subDays(2),
            'agent_id' => $owner->id,
            'user_id' => $owner->id,
            'category_id' => $parent->category_id,
        ]);
    }

    // ── show (view) ───────────────────────────────────────────────────────────

    it('agente comum acessa show de chamado encerrado antes de hoje (visualização liberada)', function () {
        $agent = actingAsAgent();

        $ticket = makeOldClosedTicket($this->company, $this->parent, $agent);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

    it('agente comum acessa show de outro status terminal encerrado antes de hoje', function () {
        $agent = actingAsAgent();

        $terminal = Status::factory()->terminal()->create([
            'name' => 'Não Resolvido',
            'requires_solution' => false,
        ]);

        $ticket = makeOldClosedTicket($this->company, $this->parent, $agent, $terminal);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

    it('agente pode visualizar chamado encerrado hoje (completed_at = today)', function () {
        $agent = actingAsAgent();
        $terminal = Status::factory()->terminal()->create([
            'name' => 'Resolvido',
        ]);

        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => $terminal->id,
            'completed_at' => now(),   // encerrado hoje — não deve bloquear
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'category_id' => $this->parent->category_id,
        ]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

    it('admin acessa show de chamado encerrado antes de hoje', function () {
        $owner = User::factory()->agent()->create();
        $admin = actingAsAdmin();

        $ticket = makeOldClosedTicket($this->company, $this->parent, $owner);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

    it('atendente especial acessa show de chamado encerrado antes de hoje', function () {
        $owner = User::factory()->agent()->create();

        // O e-mail especial definido em AccessService::isSupportAttendantEmail
        actingAsAgent(['email' => 'atendente@consuldatasistemas.com.br']);

        $ticket = makeOldClosedTicket($this->company, $this->parent, $owner);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

    // ── edit / update ─────────────────────────────────────────────────────────

    it('agente não consegue editar chamado encerrado antes de hoje (GET edit → 403)', function () {
        $owner = User::factory()->agent()->create();
        $attacker = actingAsAgent();

        $ticket = makeOldClosedTicket($this->company, $this->parent, $owner);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertForbidden();
    });

    it('admin consegue editar chamado encerrado antes de hoje', function () {
        $owner = User::factory()->agent()->create();
        $admin = actingAsAdmin();

        $ticket = makeOldClosedTicket($this->company, $this->parent, $owner);

        $this->get(route('agent.ticket.edit', $ticket->id))
            ->assertOk();
    });

    // ── chamado aberto não é afetado ──────────────────────────────────────────

    it('agente acessa show de chamado aberto (status_id != 3) normalmente', function () {
        $agent = actingAsAgent();
        $ticket = Ticket::factory()->create([
            'company_id' => $this->company->id,
            'status_id' => 1,  // pendente — aberto
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
            'category_id' => $this->parent->category_id,
        ]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk();
    });

});
