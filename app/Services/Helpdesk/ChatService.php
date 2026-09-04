<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\ChatRepositoryInterface;
use App\Models\Category;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Helpdesk\Chat\ConversationParticipant;
use App\Models\Helpdesk\Chat\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ChatService
{
    public function __construct(
        private readonly ChatRepositoryInterface $repository,
        private readonly ChatTicketService $chatTicketService,
    ) {}

    public function getActiveConversation(User $user): ?Conversation
    {
        $conversation = $this->repository->findActiveConversation($user->id);

        return $conversation ? $this->loadConversation($conversation) : null;
    }

    public function startConversation(
        User $user,
        string $subject,
        string $message,
        ?int $categoryId = null,
    ): Conversation {
        if ($activeConversation = $this->repository->findActiveConversation($user->id)) {
            return $this->loadConversation($activeConversation);
        }

        return DB::transaction(function () use ($user, $subject, $message, $categoryId) {
            $category = $categoryId ? Category::query()->find($categoryId) : null;

            $conversation = $this->repository->createConversation([
                'owner_id' => $user->id,
                'name' => $user->name,
                'desc' => $category?->display_name,
                'status_id' => 1,
                'session' => Str::uuid()->toString(),
                'password' => null,
                'expire_at' => $this->expiresAt(),
                'closed_at' => null,
                'subject' => trim($subject),
            ]);

            $this->syncParticipant($conversation, $user);

            $this->repository->createMessage([
                'chat_id' => $conversation->id,
                'user_id' => $user->id,
                'content' => trim($message),
            ]);

            $ticket = $this->chatTicketService->createForConversation(
                $conversation,
                $user,
                trim($message),
                $categoryId
            );

            $this->repository->updateConversation($conversation, [
                'ticket_id' => $ticket->id,
            ]);

            return $this->loadConversation($conversation->fresh());
        });
    }

    public function getConversationForUser(User $user, int|Conversation $conversation): Conversation
    {
        $conversationId = $conversation instanceof Conversation ? $conversation->id : $conversation;

        $ownedConversation = Conversation::query()
            ->whereKey($conversationId)
            ->where('owner_id', $user->id)
            ->firstOrFail();

        return $this->loadConversation($ownedConversation);
    }

    public function appendUserMessage(Conversation $conversation, User $user, string $message): Message
    {
        if ($conversation->isClosed()) {
            throw new \DomainException('A conversa já foi encerrada.');
        }

        return DB::transaction(function () use ($conversation, $user, $message) {
            $this->repository->updateConversation($conversation, [
                'status_id' => 2,
                'expire_at' => $this->expiresAt(),
            ]);

            $this->syncParticipant($conversation, $user);

            $chatMessage = $this->repository->createMessage([
                'chat_id' => $conversation->id,
                'user_id' => $user->id,
                'content' => trim($message),
            ]);

            $freshConversation = $conversation->fresh(['ticket']);

            if (! $freshConversation?->ticket_id) {
                $ticket = $this->chatTicketService->createForConversation(
                    $conversation,
                    $user,
                    trim($message)
                );

                $this->repository->updateConversation($conversation, [
                    'ticket_id' => $ticket->id,
                ]);
            } else {
                $this->chatTicketService->addUserMessageComment($freshConversation, $user, trim($message));
            }

            return $chatMessage;
        });
    }

    public function closeConversation(Conversation $conversation): void
    {
        if ($conversation->isClosed()) {
            return;
        }

        $this->repository->closeConversation($conversation);
    }

    private function syncParticipant(Conversation $conversation, User $user): void
    {
        ConversationParticipant::query()->updateOrCreate(
            [
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ],
            [
                'session' => $conversation->session,
                'display_name' => $user->name,
                'email' => $user->email,
                'token' => Str::uuid()->toString(),
            ],
        );
    }

    private function loadConversation(Conversation $conversation): Conversation
    {
        return $conversation->load([
            'owner',
            'agent',
            'status',
            'ticket.status',
            'ticket.company',
            'messages.owner',
        ]);
    }

    private function expiresAt(): \Illuminate\Support\Carbon
    {
        return now()->addHours(max(1, (int) config('helpdesk.chat.expire_hours', 12)));
    }
}
