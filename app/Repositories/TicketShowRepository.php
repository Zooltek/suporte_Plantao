<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TicketShowRepositoryInterface;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Comment;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Collection;

class TicketShowRepository implements TicketShowRepositoryInterface
{
    /**
     * Eager-load todas as relações necessárias para a view de exibição do ticket.
     * Resolve N+1 de company.group, company.contacts e company.moduleTypes.
     */
    public function loadForDisplay(Ticket $ticket): Ticket
    {
        return $ticket->load([
            'status',
            'category.description',
            'subCategory.description',
            'agent',
            'department',
            'origin',
            'author',
            'company',
            'attendances.user',
            'attendances.returnAssignee',
            'attendances.returnedBy',
            'referencedTicket',
            'childTickets',
            'extraCategories.category.description',
            'extraCategories.subCategory.description',
            'company.group',
            'company.contacts',
            'company.moduleTypes',
            'issues' => fn ($query) => $query->orderBy('created_at', 'asc'),
            'issues.creator',
            'issues.resolver',
        ]);
    }

    /**
     * Comentários do ticket em ordem cronológica crescente, com user eager-loaded.
     */
    public function getComments(int $ticketId): Collection
    {
        return Comment::with('user')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Anexos ativos do ticket em ordem cronológica decrescente.
     */
    public function getAttachments(int $ticketId): Collection
    {
        return Attachment::where('ticket_id', $ticketId)
            ->where('status', 1)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Todos os statuses ordenados por nome.
     */
    public function getAllStatuses(): Collection
    {
        return Status::orderedDistinctForSelection();
    }

    /**
     * Agentes ativos com ticketit_agent = 1, ordenados por nome.
     */
    public function getAgentsList(): Collection
    {
        return Agent::where('ticketit_agent', 1)
            ->orderBy('name')
            ->get();
    }
}
