<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Ticket\Comment;
use App\Models\Ticket\Ticket;

interface TicketCommentRepositoryInterface
{
    /**
     * Persiste (INSERT) um novo comentário no banco e retorna a instância.
     */
    public function createComment(string $purifiedContent, int $ticketId, int $userId): Comment;

    /**
     * Persiste (UPDATE) o ticket com os atributos fornecidos.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function updateTicket(Ticket $ticket, array $attributes): void;

    /**
     * Busca o Agent pelo id; retorna null se não encontrado.
     */
    public function findAgent(int $userId): ?\App\Models\Ticket\Agent;
}
