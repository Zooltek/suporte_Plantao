<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;

/**
 * Cobre o botão "Puxar para mim" no index de chamados
 * e o endpoint de captura.
 */
describe('TicketIndex — botão "Puxar para mim"', function () {

    it('admin vê botão Puxar para mim para ticket sem agente no index', function () {
        // Admins veem todos os tickets (incluindo sem agente) — o botão deve aparecer
        $admin = actingAsAdmin();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => null,  // sem agente
            'user_id' => $admin->id,
        ]);

        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertSee('Puxar para mim');
    });

    it('admin não vê botão Puxar para mim quando ticket já tem agente', function () {
        $admin = actingAsAdmin();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => $admin->id,  // já tem agente
            'user_id' => $admin->id,
        ]);

        $this->get(route('agent.ticket.index'))
            ->assertOk()
            ->assertDontSee('Puxar para mim');
    });

    it('captura redireciona para o ticket e atribui o agente', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => null,
            'user_id' => $agent->id,
        ]);

        $this->post(route('agent.ticket.capture', $ticket->id), ['ticket_id' => $ticket->id])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));

        expect($ticket->fresh()->agent_id)->toBe($agent->id);
    });

    it('captura também funciona quando o ticket sem responsável usa agent_id 0', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => 0,
            'user_id' => $agent->id,
        ]);

        $this->post(route('agent.ticket.capture', $ticket->id), ['ticket_id' => $ticket->id])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));

        expect($ticket->fresh()->agent_id)->toBe($agent->id);
    });

    it('captura reatribui para o agente logado quando o ticket já pertence a outro agente', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $other = \App\Models\User::factory()->create(['ticketit_agent' => 1, 'ticketit_admin' => 0]);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => $other->id,
            'user_id' => $agent->id,
        ]);

        $this->post(route('agent.ticket.capture', $ticket->id), ['ticket_id' => $ticket->id])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));

        expect($ticket->fresh()->agent_id)->toBe($agent->id);
    });

    it('captura retorna warning quando o chamado já pertence ao agente logado', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
        ]);

        $this->post(route('agent.ticket.capture', $ticket->id), ['ticket_id' => $ticket->id])
            ->assertRedirect()
            ->assertSessionHas('warning', 'Você já capturou o chamado.');
    });

    it('detalhe exibe botão de captura para chamado sem agente', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => null,
            'user_id' => $agent->id,
        ]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertSee('Puxar para mim');
    });

    it('detalhe exibe botão de captura para chamado de outro agente', function () {
        $agent = actingAsAgent();
        $other = \App\Models\User::factory()->agent()->create();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => $other->id,
            'user_id' => $agent->id,
        ]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertSee('Puxar para mim');
    });

    it('detalhe não exibe botão de captura quando o chamado já pertence ao agente logado', function () {
        $agent = actingAsAgent();

        $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
        $status = Status::factory()->create(['requires_agent' => false, 'is_terminal' => false, 'requires_schedule' => false]);
        $parent = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $ticket = Ticket::factory()->create([
            'company_id' => $company->id,
            'status_id' => $status->id,
            'category_id' => $parent->category_id,
            'agent_id' => $agent->id,
            'user_id' => $agent->id,
        ]);

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertDontSee('Puxar para mim');
    });
});
