<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Ticket\Agent;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Comment;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Collection;

interface TicketShowRepositoryInterface
{
    /**
     * Eager-load todas as relações necessárias para a view de exibição do ticket.
     */
    public function loadForDisplay(Ticket $ticket): Ticket;

    /**
     * Retorna comentários do ticket em ordem cronológica crescente, com user.
     */
    public function getComments(int $ticketId): Collection;

    /**
     * Retorna anexos ativos do ticket em ordem cronológica decrescente.
     */
    public function getAttachments(int $ticketId): Collection;

    /**
     * Retorna todos os statuses ordenados por nome.
     */
    public function getAllStatuses(): Collection;

    /**
     * Retorna todos os agentes ativos (ticketit_agent = 1), ordenados por nome.
     */
    public function getAgentsList(): Collection;
}
