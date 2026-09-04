<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\Message;
use Illuminate\Database\Eloquent\Collection;

interface ChatRepositoryInterface
{
    /**
     * Retorna a conversa ativa do usuário (status_id <= 3), se existir.
     */
    public function findActiveConversation(int $userId): ?Conversation;

    /**
     * Persiste uma nova conversa e retorna o modelo.
     */
    public function createConversation(array $data): Conversation;

    /**
     * Atualiza a descrição de uma conversa existente.
     */
    public function updateConversation(Conversation $conversation, array $data): bool;

    /**
     * Fecha a conversa (status_id = 4, closed_at = now).
     */
    public function closeConversation(Conversation $conversation): bool;

    /**
     * Busca a conversa com owner carregado; lança ModelNotFoundException.
     */
    public function findWithOwner(int $conversationId): Conversation;

    /**
     * Persiste uma mensagem de chat no banco.
     */
    public function createMessage(array $data): Message;

    /**
     * Retorna todas as mensagens de uma conversa.
     */
    public function getMessagesByConversation(int $conversationId): Collection;
}
