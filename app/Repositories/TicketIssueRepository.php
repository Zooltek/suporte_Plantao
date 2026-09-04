<?php

namespace App\Repositories;

use App\Contracts\Repositories\TicketIssueRepositoryInterface;
use App\Models\Ticket\TicketIssue;
use Illuminate\Support\Collection;

class TicketIssueRepository implements TicketIssueRepositoryInterface
{
    public function allForTicket(int $ticketId): Collection
    {
        return TicketIssue::with(['creator', 'resolver'])
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function create(array $data): TicketIssue
    {
        return TicketIssue::create($data);
    }

    public function update(TicketIssue $issue, array $data): TicketIssue
    {
        $issue->update($data);

        return $issue->fresh(['creator', 'resolver']);
    }

    public function delete(TicketIssue $issue): void
    {
        $issue->delete();
    }
}
