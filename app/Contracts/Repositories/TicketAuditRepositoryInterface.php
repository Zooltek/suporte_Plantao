<?php

namespace App\Contracts\Repositories;

use App\Models\Ticket\TicketAudit;
use Illuminate\Support\Collection;

interface TicketAuditRepositoryInterface
{
    public function allForTicket(int $ticketId): Collection;

    public function create(array $data): TicketAudit;
}
