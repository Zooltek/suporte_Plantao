<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TicketCommentRepositoryInterface;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;

class TicketCommentRepository implements TicketCommentRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function createComment(string $purifiedContent, int $ticketId, int $userId): Comment
    {
        $comment = new Comment();
        $comment->setPurifiedContent($purifiedContent);
        $comment->ticket_id = $ticketId;
        $comment->user_id   = $userId;
        $comment->save();

        return $comment;
    }

    /**
     * {@inheritDoc}
     */
    public function updateTicket(Ticket $ticket, array $attributes): void
    {
        $ticket->fill($attributes)->save();
    }

    /**
     * {@inheritDoc}
     */
    public function findAgent(int $userId): ?Agent
    {
        return Agent::find($userId);
    }
}
