<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ChatRepositoryInterface;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\Message;
use Illuminate\Database\Eloquent\Collection;

class ChatRepository implements ChatRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findActiveConversation(int $userId): ?Conversation
    {
        return Conversation::where('owner_id', $userId)
            ->where('status_id', '!=', 4)
            ->latest('id')
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function createConversation(array $data): Conversation
    {
        $conversation = new Conversation($data);
        $conversation->save();

        return $conversation;
    }

    /**
     * {@inheritDoc}
     */
    public function updateConversation(Conversation $conversation, array $data): bool
    {
        return $conversation->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function closeConversation(Conversation $conversation): bool
    {
        return $conversation->update([
            'status_id' => 4,
            'expire_at' => now(),
            'closed_at' => now(),
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findWithOwner(int $conversationId): Conversation
    {
        return Conversation::with('owner')->findOrFail($conversationId);
    }

    /**
     * {@inheritDoc}
     */
    public function createMessage(array $data): Message
    {
        return Message::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getMessagesByConversation(int $conversationId): Collection
    {
        return Message::with('owner')
            ->where('chat_id', $conversationId)
            ->orderBy('created_at')
            ->get();
    }
}
