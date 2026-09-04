<?php

namespace App\Services\API\V1\Tickets;

use App\Contracts\Repositories\AttendanceRepositoryInterface;
use App\Models\Ticket\Attendance;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AttendanceService
{
    public function __construct(
        private readonly AttendanceRepositoryInterface $attendanceRepository,
    ) {}

    /**
     * Retorna os atendimentos de um ticket ordenados do mais recente.
     */
    public function listForTicket(int $ticketId): Collection
    {
        return $this->attendanceRepository->listForTicket($ticketId);
    }

    /**
     * Cria um novo atendimento distinguindo retorno realizado de retorno agendado.
     */
    public function create(int $ticketId, array $data): Attendance
    {
        $ticket = $this->attendanceRepository->findTicketOrFail($ticketId);

        $hasReturn    = ! empty($data['return_zap']) || ! empty($data['return_tel']) || ! empty($data['return_cel']);
        $returnAt     = $data['return_at'] ?? null;
        $returnUserId = $data['return_user_id'] ?? null;
        $hasScheduledReturn = $hasReturn && ($returnAt !== null || $returnUserId !== null);
        $authUserId = Auth::id();

        return $this->attendanceRepository->create([
            'ticket_id'           => $ticket->id,
            'user_id'             => $authUserId,
            'notes'               => $data['notes']      ?? null,
            'return_zap'          => (bool) ($data['return_zap'] ?? false),
            'return_tel'          => (bool) ($data['return_tel'] ?? false),
            'return_cel'          => (bool) ($data['return_cel'] ?? false),
            'return_assigned_to'  => $hasScheduledReturn ? $returnUserId : null,
            'return_scheduled_at' => $hasScheduledReturn ? ($returnAt ? Carbon::parse($returnAt) : now()) : null,
            'returned_by'         => $hasReturn && ! $hasScheduledReturn ? $authUserId : null,
            'returned_at'         => $hasReturn && ! $hasScheduledReturn ? now() : null,
        ]);
    }
}
