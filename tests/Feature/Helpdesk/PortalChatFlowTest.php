<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\User;

function portal_chat_catalog(): array
{
    $company = Company::factory()->create([
        'is_active' => true,
        'financial_irregular' => false,
    ]);

    $category = Category::factory()
        ->withDescription('Portal Web')
        ->create([
            'parent_id' => 0,
            'priority' => 'low',
        ]);

    $origin = Origin::factory()->create(['name' => 'Portal do Cliente']);
    $status = Status::factory()->create(['name' => 'Pendente']);
    $priority = Priority::factory()->create(['name' => 'Normal']);

    config()->set('helpdesk.chat.origin_id', $origin->id);
    config()->set('helpdesk.chat.default_status_id', $status->id);
    config()->set('helpdesk.chat.default_priority_id', $priority->id);
    config()->set('helpdesk.chat.default_category_id', $category->category_id);
    config()->set('helpdesk.chat.fallback_company_id', $company->id);
    config()->set('helpdesk.chat.expire_hours', 12);

    return compact('company', 'category', 'origin', 'status', 'priority');
}

describe('PortalChatFlowTest', function () {
    it('redireciona visitante para o login ao acessar o portal', function () {
        $this->get(route('portal.chat.index'))
            ->assertRedirect(route('login'));
    });

    it('usuário comum autenticado consegue abrir um chat e criar ticket vinculado', function () {
        $catalog = portal_chat_catalog();
        $user = actingAsUser(['company_id' => $catalog['company']->id]);

        $this->post(route('portal.chat.store'), [
            'subject' => 'Erro ao emitir nota',
            'category_id' => $catalog['category']->category_id,
            'message' => 'Ao tentar emitir a nota, o sistema fecha sem concluir a operação.',
        ])->assertRedirect();

        $conversation = Conversation::query()->where('owner_id', $user->id)->firstOrFail();

        expect($conversation->ticket_id)->not->toBeNull()
            ->and($conversation->subject)->toBe('Erro ao emitir nota');

        $this->assertDatabaseHas('chat_messages', [
            'chat_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Ao tentar emitir a nota, o sistema fecha sem concluir a operação.',
        ]);

        $this->assertDatabaseHas('chat_participants', [
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        $this->assertDatabaseHas('ticketit', [
            'id' => $conversation->ticket_id,
            'author_id' => $user->id,
            'user_id' => $user->id,
            'company_id' => $catalog['company']->id,
            'subject' => 'Erro ao emitir nota',
            'content' => 'Ao tentar emitir a nota, o sistema fecha sem concluir a operação.',
        ]);
    });

    it('mensagens adicionais do usuário entram no transcript e viram comentário do ticket', function () {
        $catalog = portal_chat_catalog();
        $user = actingAsUser(['company_id' => $catalog['company']->id]);

        $this->post(route('portal.chat.store'), [
            'subject' => 'Falha no financeiro',
            'category_id' => $catalog['category']->category_id,
            'message' => 'Primeira descrição do problema financeiro.',
        ]);

        $conversation = Conversation::query()->where('owner_id', $user->id)->firstOrFail();

        $this->post(route('portal.chat.message.store', $conversation), [
            'message' => 'Nova informação: o erro ocorre apenas no fechamento do caixa.',
        ])->assertRedirect(route('portal.chat.show', $conversation));

        $this->assertDatabaseHas('chat_messages', [
            'chat_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Nova informação: o erro ocorre apenas no fechamento do caixa.',
        ]);

        $this->assertDatabaseHas('ticketit_comments', [
            'ticket_id' => $conversation->ticket_id,
            'user_id' => $user->id,
            'content' => 'Nova informação: o erro ocorre apenas no fechamento do caixa.',
        ]);
    });

    it('usuário pode encerrar a sessão do chat sem remover o ticket', function () {
        $catalog = portal_chat_catalog();
        $user = actingAsUser(['company_id' => $catalog['company']->id]);

        $this->post(route('portal.chat.store'), [
            'subject' => 'Chat para encerrar',
            'category_id' => $catalog['category']->category_id,
            'message' => 'Mensagem inicial para criação do ticket.',
        ]);

        $conversation = Conversation::query()->where('owner_id', $user->id)->firstOrFail();

        $this->post(route('portal.chat.close', $conversation))
            ->assertRedirect(route('portal.chat.index'));

        $this->assertDatabaseHas('chats', [
            'id' => $conversation->id,
            'status_id' => 4,
        ]);

        $this->assertDatabaseHas('ticketit', [
            'id' => $conversation->ticket_id,
        ]);
    });

    it('portal redireciona o usuário para a conversa ativa em vez de abrir uma segunda sessão', function () {
        $catalog = portal_chat_catalog();
        $user = actingAsUser(['company_id' => $catalog['company']->id]);

        $this->post(route('portal.chat.store'), [
            'subject' => 'Conversa única',
            'category_id' => $catalog['category']->category_id,
            'message' => 'Mensagem inicial da conversa única.',
        ]);

        $conversation = Conversation::query()->where('owner_id', $user->id)->firstOrFail();

        $this->get(route('portal.chat.index'))
            ->assertRedirect(route('portal.chat.show', $conversation));

        expect(Conversation::query()->where('owner_id', $user->id)->count())->toBe(1);
    });
});
