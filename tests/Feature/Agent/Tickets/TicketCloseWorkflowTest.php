<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Support\Tickets\TicketStatusCatalog;
use Database\Seeders\Helpdesk\Ticketit\StatusSeeder;

function tcw_company(): Company
{
    return Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
}

function tcw_category(): Category
{
    return Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
}

function tcw_ticket(User $agent, Company $company, Category $category, ?Status $status = null): Ticket
{
    return Ticket::factory()->create([
        'company_id' => $company->id,
        'agent_id' => $agent->id,
        'author_id' => $agent->id,
        'user_id' => $agent->id,
        'category_id' => $category->category_id,
        'status_id' => $status?->id ?? 2,
    ]);
}

describe('Ticket close workflow', function () {

    beforeEach(function () {
        $this->seed(StatusSeeder::class);
    });

    it('fecha como Não Resolvido e redireciona para a fila de pendências', function () {
        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($agent, $company, $category);
        $notSolved = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $this->post(route('agent.ticket.close', $ticket->id), [
            'status_id' => $notSolved->id,
        ])->assertRedirect(route('agent.calendar.condensed', ['active' => 'pending']));

        $ticket->refresh();

        expect($ticket->status_id)->toBe($notSolved->id)
            ->and($ticket->completed_at)->not->toBeNull();
    });

    it('retorna 403 quando o usuário não pode encerrar o chamado', function () {
        $owner = User::factory()->agent()->create();
        $attacker = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($owner, $company, $category);
        $notSolved = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $this->post(route('agent.ticket.close', $ticket->id), [
            'status_id' => $notSolved->id,
        ])->assertForbidden();
    });

    it('impede encerrar como Resolvido sem informar solução', function () {
        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($agent, $company, $category);
        $resolved = Status::query()->findOrFail(TicketStatusCatalog::RESOLVED_ID);

        $this->from(route('agent.ticket.show', $ticket->id))
            ->post(route('agent.ticket.close', $ticket->id), [
                'status_id' => $resolved->id,
                'solution' => '',
            ])->assertRedirect(route('agent.ticket.show', $ticket->id))
            ->assertSessionHasErrors('solution');

        $ticket->refresh();

        expect($ticket->status_id)->not->toBe($resolved->id)
            ->and($ticket->completed_at)->toBeNull();
    });

    it('não exibe o CTA de encerramento para ticket já terminal', function () {
        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $terminal = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);
        $ticket = tcw_ticket($agent, $company, $category, $terminal);
        $ticket->forceFill(['completed_at' => now()])->save();

        $this->get(route('agent.ticket.show', $ticket->id))
            ->assertOk()
            ->assertDontSee(route('agent.ticket.close', $ticket->id))
            ->assertDontSee('Responder ao Chamado')
            ->assertSee('Chamado já encerrado');
    });

    it('não redireciona novamente para a tela do chamado ao encerrar', function () {
        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($agent, $company, $category);
        $notSolved = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $response = $this->post(route('agent.ticket.close', $ticket->id), [
            'status_id' => $notSolved->id,
        ]);

        expect($response->headers->get('Location'))->not->toBe(route('agent.ticket.show', $ticket->id));
    });

    it('registra encerramento e agenda liberação do bot WhatsApp ao encerrar chamado vinculado', function () {
        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($agent, $company, $category);
        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'last_activity_at' => now()->subMinute(),
        ]);
        $notSolved = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $this->post(route('agent.ticket.close', $ticket->id), [
            'status_id' => $notSolved->id,
        ])->assertRedirect(route('agent.calendar.condensed', ['active' => 'pending']));

        $conversation->refresh();

        expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conversation->getPayloadValue('bot_release_after'))->not->toBeNull()
            ->and($conversation->getPayloadValue('ticket_closed_at'))->not->toBeNull();
    });

    it('libera imediatamente o bot WhatsApp quando o delay configurado for 0', function () {
        config(['whatsapp.chatbot.ticket_closed_delay_minutes' => 0]);

        $agent = actingAsAgent();
        $company = tcw_company();
        $category = tcw_category();
        $ticket = tcw_ticket($agent, $company, $category);
        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'last_activity_at' => now()->subMinute(),
        ]);
        $notSolved = Status::query()->findOrFail(TicketStatusCatalog::UNRESOLVED_ID);

        $this->post(route('agent.ticket.close', $ticket->id), [
            'status_id' => $notSolved->id,
        ])->assertRedirect(route('agent.calendar.condensed', ['active' => 'pending']));

        $conversation->refresh();

        expect($conversation->state)->toBe(ConversationState::COMPLETED)
            ->and($conversation->getPayloadValue('bot_auto_release_reason'))->toBe('ticket_closed');
    });

});
