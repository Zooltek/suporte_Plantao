<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\Message;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Str;

function agent_portal_chat_catalog(): array
{
    $company = Company::factory()->create([
        'is_active' => true,
        'financial_irregular' => false,
    ]);

    $category = Category::factory()
        ->withDescription('Suporte Chat')
        ->create([
            'parent_id' => 0,
            'priority' => 'low',
        ]);

    $origin = Origin::factory()->create(['name' => 'Portal do Cliente']);
    $status = Status::factory()->create(['name' => 'Pendente']);
    $priority = Priority::factory()->create(['name' => 'Normal']);

    return compact('company', 'category', 'origin', 'status', 'priority');
}

describe('AgentCommentSyncToPortalChatTest', function () {
    it('espelha a resposta do agente para o transcript do chat web', function () {
        $catalog = agent_portal_chat_catalog();
        $customer = User::factory()->create([
            'company_id' => $catalog['company']->id,
        ]);

        $ticket = Ticket::factory()->create([
            'author_id' => $customer->id,
            'user_id' => $customer->id,
            'company_id' => $catalog['company']->id,
            'origin_id' => $catalog['origin']->id,
            'status_id' => $catalog['status']->id,
            'priority_id' => $catalog['priority']->id,
            'category_id' => $catalog['category']->category_id,
            'sub_category_id' => $catalog['category']->category_id,
            'subject' => 'Falha no módulo fiscal',
            'content' => 'Cliente relatou erro no módulo fiscal.',
        ]);

        $conversation = Conversation::factory()->create([
            'owner_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status_id' => 1,
            'session' => Str::uuid()->toString(),
            'subject' => 'Falha no módulo fiscal',
        ]);

        Message::factory()->create([
            'chat_id' => $conversation->id,
            'user_id' => $customer->id,
            'content' => 'Cliente relatou erro no módulo fiscal.',
        ]);

        $agent = actingAsAgent();

        $this->post(route('agent.ticket.comment.store', $ticket), [
            'content' => 'Vamos analisar esse erro e retornar com a correção.',
        ])->assertRedirect(route('agent.ticket.show', $ticket));

        $this->assertDatabaseHas('ticketit_comments', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'content' => 'Vamos analisar esse erro e retornar com a correção.',
        ]);

        $this->assertDatabaseHas('chat_messages', [
            'chat_id' => $conversation->id,
            'user_id' => $agent->id,
            'content' => 'Vamos analisar esse erro e retornar com a correção.',
        ]);
    });
});
