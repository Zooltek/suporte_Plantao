<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\TicketCommentRepositoryInterface;
use App\Models\Category;
use App\Models\Company;
use App\Models\Helpdesk\Chat\Conversation;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ChatTicketService
{
    public function __construct(
        private readonly TicketCommentRepositoryInterface $ticketCommentRepository,
    ) {}

    public function createForConversation(
        Conversation $conversation,
        User $user,
        string $initialMessage,
        ?int $categoryId = null,
    ): Ticket {
        return DB::transaction(function () use ($conversation, $user, $initialMessage, $categoryId) {
            $resolvedCategoryId = $this->resolveCategoryId($categoryId);

            $ticket = new Ticket();
            $ticket->origin_id = $this->resolveConfiguredId(
                Origin::class,
                (int) config('helpdesk.chat.origin_id', 2),
                'origem do chat web'
            );
            $ticket->status_id = $this->resolveConfiguredId(
                Status::class,
                (int) config('helpdesk.chat.default_status_id', Ticket::STATUS_PENDING_ID),
                'status padrão do chat web'
            );
            $ticket->priority_id = $this->resolveConfiguredId(
                Priority::class,
                (int) config('helpdesk.chat.default_priority_id', 1),
                'prioridade padrão do chat web'
            );
            $ticket->author_id = $user->id;
            $ticket->user_id = $user->id;
            $ticket->agent_id = null;
            $ticket->company_id = $this->resolveCompanyId($user);
            $ticket->contact = mb_strtoupper($user->name);
            $ticket->trouble = $initialMessage;
            $ticket->solution = null;
            $ticket->obs = "Chamado aberto via chat web. Sessão: {$conversation->session}";
            $ticket->category_id = $resolvedCategoryId;
            $ticket->sub_category_id = $resolvedCategoryId;
            $ticket->visible = 0;
            $ticket->subject = trim((string) $conversation->subject) !== ''
                ? $conversation->subject
                : 'Chat Web - '.mb_strtoupper($user->name);
            $ticket->content = $initialMessage;
            $ticket->created_at = now();
            $ticket->save();

            return $ticket;
        });
    }

    public function addUserMessageComment(Conversation $conversation, User $user, string $message): void
    {
        if (! $conversation->ticket_id) {
            return;
        }

        $ticket = $conversation->ticket ?: Ticket::query()->find($conversation->ticket_id);

        if (! $ticket) {
            return;
        }

        $comment = $this->ticketCommentRepository->createComment($message, $ticket->id, $user->id);

        $this->ticketCommentRepository->updateTicket($ticket, [
            'updated_at' => $comment->created_at,
        ]);
    }

    private function resolveCompanyId(User $user): int
    {
        if ($user->company_id && Company::query()->whereKey($user->company_id)->exists()) {
            return (int) $user->company_id;
        }

        return $this->resolveConfiguredId(
            Company::class,
            (int) config('helpdesk.chat.fallback_company_id', 1),
            'empresa padrão do chat web'
        );
    }

    private function resolveCategoryId(?int $categoryId): int
    {
        if ($categoryId && Category::query()->whereKey($categoryId)->exists()) {
            return $categoryId;
        }

        return $this->resolveConfiguredId(
            Category::class,
            (int) config('helpdesk.chat.default_category_id', 1),
            'categoria padrão do chat web'
        );
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function resolveConfiguredId(string $modelClass, int $configuredId, string $context): int
    {
        if ($configuredId > 0 && $modelClass::query()->whereKey($configuredId)->exists()) {
            return $configuredId;
        }

        $fallbackId = $this->firstAvailableId($modelClass);

        if ($fallbackId !== null) {
            return $fallbackId;
        }

        throw new RuntimeException("Não foi possível resolver {$context}.");
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    private function firstAvailableId(string $modelClass): ?int
    {
        /** @var Model $model */
        $model = new $modelClass();
        $keyName = $model->getKeyName();
        $firstId = $modelClass::query()->orderBy($keyName)->value($keyName);

        return $firstId !== null ? (int) $firstId : null;
    }
}
