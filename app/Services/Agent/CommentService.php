<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\TicketCommentRepositoryInterface;
use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;
use App\Services\Helpdesk\ChatTranscriptService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

/**
 * Responsabilidade única: gerenciamento de comentários em tickets.
 *
 * SRP: separado do TicketService (save ticket) e TicketShowService (read-only).
 */
class CommentService
{
    public function __construct(
        private readonly TicketCommentRepositoryInterface $commentRepository,
        private readonly ChatTranscriptService $chatTranscriptService,
    ) {}

    /**
     * Cria um comentário e atualiza o estado do ticket conforme o papel do usuário.
     */
    public function addComment(Ticket $ticket, string $content, Authenticatable $user): Comment
    {
        return DB::transaction(function () use ($ticket, $content, $user) {
            $comment = $this->commentRepository->createComment($content, $ticket->id, $user->id);

            // Agente/Admin responde → ticket muda de status e o agente é atribuído
            $agent = $this->commentRepository->findAgent($user->id);

            $updates = ['updated_at' => $comment->created_at];

            if ($agent && ($agent->isAgent() || $agent->isAdmin())) {
                $updates['status_id'] = Config::get('ticketit.defaults.status_reply_id', 4);
                $updates['agent_id'] = $user->id;

                $this->chatTranscriptService->mirrorAgentComment($ticket, $user->id, $content);
            }

            $this->commentRepository->updateTicket($ticket, $updates);

            return $comment;
        });
    }
}
