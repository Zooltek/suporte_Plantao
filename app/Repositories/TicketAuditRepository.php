<?php

namespace App\Repositories;

use App\Contracts\Repositories\TicketAuditRepositoryInterface;
use App\Models\Ticket\TicketAudit;
use Illuminate\Support\Collection;

class TicketAuditRepository implements TicketAuditRepositoryInterface
{
    public function allForTicket(int $ticketId): Collection
    {
        return TicketAudit::with('user')
            ->where('ticket_id', $ticketId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): TicketAudit
    {
        return TicketAudit::create($data);
    }
}
