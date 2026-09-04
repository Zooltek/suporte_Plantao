<?php

namespace App\Services\Ticket;

use App\Contracts\Repositories\TicketIssueRepositoryInterface;
use App\Models\Ticket\TicketIssue;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class TicketIssueService
{
    public function __construct(
        private readonly TicketIssueRepositoryInterface $repository,
    ) {}

    public function listForTicket(int $ticketId): Collection
    {
        return $this->repository->allForTicket($ticketId);
    }

    public function create(int $ticketId, array $data): TicketIssue
    {
        return $this->repository->create([
            'ticket_id'   => $ticketId,
            'created_by'  => Auth::id(),
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'status'      => 'open',
        ]);
    }

    public function resolve(TicketIssue $issue, string $solution): TicketIssue
    {
        return $this->repository->update($issue, [
            'status'      => 'resolved',
            'solution'    => $solution,
            'resolved_at' => now(),
            'resolved_by' => Auth::id(),
        ]);
    }

    public function delete(TicketIssue $issue): void
    {
        $this->repository->delete($issue);
    }
}
