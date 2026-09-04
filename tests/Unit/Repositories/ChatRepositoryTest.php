<?php

/**
 * Testes de integração — ChatRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\Message;
use App\Models\User;
use App\Repositories\ChatRepository;
use Illuminate\Support\Str;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function chat_repo(): ChatRepository
{
    return new ChatRepository();
}

function make_conversation(array $attrs = []): Conversation
{
    return Conversation::factory()->create($attrs);
}

// ─── findActiveConversation ───────────────────────────────────────────────────

describe('ChatRepository — findActiveConversation', function () {

    it('retorna conversa ativa do usuário (status_id <= 3)', function () {
        $user         = User::factory()->create();
        $conversation = make_conversation(['owner_id' => $user->id, 'status_id' => 1]);

        $result = chat_repo()->findActiveConversation($user->id);

        expect($result)->not->toBeNull();
        expect($result->id)->toBe($conversation->id);
    });

    it('retorna null quando não há conversa ativa (status_id = 4)', function () {
        $user = User::factory()->create();
        make_conversation(['owner_id' => $user->id, 'status_id' => 4]);

        $result = chat_repo()->findActiveConversation($user->id);

        expect($result)->toBeNull();
    });

});

// ─── createConversation ───────────────────────────────────────────────────────

describe('ChatRepository — createConversation', function () {

    it('persiste a nova conversa no banco', function () {
        $user = User::factory()->create();

        $conversation = chat_repo()->createConversation([
            'owner_id'  => $user->id,
            'agent_id'  => $user->id,
            'name'      => 'Em Progresso',
            'desc'      => 'Suporte',
            'status_id' => 1,
            'session'   => Str::uuid()->toString(),
            'password'  => bcrypt('secret'),
            'expire_at' => now()->addDays(7),
            'closed_at' => now()->addDays(7),
        ]);

        expect($conversation)->toBeInstanceOf(Conversation::class);
        expect($conversation->exists)->toBeTrue();

        $this->assertDatabaseHas('chats', ['id' => $conversation->id]);
    });

});

// ─── updateConversation ───────────────────────────────────────────────────────

describe('ChatRepository — updateConversation', function () {

    it('atualiza o campo desc da conversa', function () {
        $conversation = make_conversation(['desc' => 'Original']);

        $result = chat_repo()->updateConversation($conversation, ['desc' => 'Atualizado']);

        expect($result)->toBeTrue();
        $this->assertDatabaseHas('chats', ['id' => $conversation->id, 'desc' => 'Atualizado']);
    });

});

// ─── closeConversation ────────────────────────────────────────────────────────

describe('ChatRepository — closeConversation', function () {

    it('fecha a conversa com status_id = 4 e preenche closed_at', function () {
        $conversation = make_conversation(['status_id' => 1]);

        chat_repo()->closeConversation($conversation);

        $this->assertDatabaseHas('chats', [
            'id'        => $conversation->id,
            'status_id' => 4,
        ]);

        $fresh = $conversation->fresh();
        expect($fresh->closed_at)->not->toBeNull();
    });

});

// ─── findWithOwner ────────────────────────────────────────────────────────────

describe('ChatRepository — findWithOwner', function () {

    it('retorna conversa com owner carregado', function () {
        $conversation = make_conversation();

        $result = chat_repo()->findWithOwner($conversation->id);

        expect($result)->toBeInstanceOf(Conversation::class);
        expect($result->relationLoaded('owner'))->toBeTrue();
    });

    it('lança ModelNotFoundException quando não existe', function () {
        expect(fn () => chat_repo()->findWithOwner(999999))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── createMessage / getMessagesByConversation ────────────────────────────────

describe('ChatRepository — createMessage e getMessagesByConversation', function () {

    it('persiste mensagem e a retorna pelo conversation_id', function () {
        $conversation = make_conversation();
        $user         = User::factory()->create();

        $message = chat_repo()->createMessage([
            'chat_id' => $conversation->id,
            'user_id' => $user->id,
            'content' => 'Olá, preciso de ajuda.',
        ]);

        expect($message)->toBeInstanceOf(Message::class);
        expect($message->exists)->toBeTrue();

        $messages = chat_repo()->getMessagesByConversation($conversation->id);

        expect($messages->count())->toBe(1);
        expect($messages->first()->content)->toBe('Olá, preciso de ajuda.');
    });

});
