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

describe('WebChatConversationTabTest', function () {
    it('exibe a aba de chat web no detalhe do ticket quando há conversa vinculada', function () {
        $company = Company::factory()->create([
            'is_active' => true,
            'financial_irregular' => false,
        ]);

        $category = Category::factory()
            ->withDescription('Atendimento Web')
            ->create([
                'parent_id' => 0,
                'priority' => 'low',
            ]);

        $origin = Origin::factory()->create(['name' => 'Portal do Cliente']);
        $status = Status::factory()->create(['name' => 'Pendente']);
        $priority = Priority::factory()->create(['name' => 'Normal']);
        $customer = User::factory()->create(['company_id' => $company->id]);

        $ticket = Ticket::factory()->create([
            'author_id' => $customer->id,
            'user_id' => $customer->id,
            'company_id' => $company->id,
            'origin_id' => $origin->id,
            'status_id' => $status->id,
            'priority_id' => $priority->id,
            'category_id' => $category->category_id,
            'sub_category_id' => $category->category_id,
            'subject' => 'Cliente sem retorno de impressão',
            'content' => 'Cliente informa que a impressão não inicia.',
        ]);

        $conversation = Conversation::factory()->create([
            'owner_id' => $customer->id,
            'ticket_id' => $ticket->id,
            'status_id' => 2,
            'session' => Str::uuid()->toString(),
            'subject' => 'Cliente sem retorno de impressão',
        ]);

        Message::factory()->create([
            'chat_id' => $conversation->id,
            'user_id' => $customer->id,
            'content' => 'Cliente informa que a impressão não inicia.',
        ]);

        Message::factory()->create([
            'chat_id' => $conversation->id,
            'user_id' => $customer->id,
            'content' => 'O erro começou após a atualização do PDV.',
        ]);

        actingAsAgent();

        $this->get(route('agent.ticket.show', $ticket))
            ->assertOk()
            ->assertSee('Chat Web')
            ->assertSee('Cliente sem retorno de impressão')
            ->assertSee('Cliente informa que a impressão não inicia.');
    });
});
