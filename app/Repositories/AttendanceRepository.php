<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AttendanceRepositoryInterface;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Collection;

class AttendanceRepository implements AttendanceRepositoryInterface
{
    public function findTicketOrFail(int $ticketId): Ticket
    {
        return Ticket::findOrFail($ticketId);
    }

    public function listForTicket(int $ticketId): Collection
    {
        $ticket = $this->findTicketOrFail($ticketId);

        return $ticket->attendances()
            ->with(['user', 'returnedBy', 'returnAssignee'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function create(array $data): Attendance
    {
        return Attendance::create($data);
    }
}
