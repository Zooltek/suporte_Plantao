<?php

namespace App\Contracts\Repositories;

use App\Models\Ticket\TicketIssue;
use Illuminate\Support\Collection;

interface TicketIssueRepositoryInterface
{
    public function allForTicket(int $ticketId): Collection;

    public function create(array $data): TicketIssue;

    public function update(TicketIssue $issue, array $data): TicketIssue;

    public function delete(TicketIssue $issue): void;
}
