<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ScheduleRepositoryInterface;
use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Models\Ticket\Ticket;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    /**
     * Persiste (INSERT ou UPDATE) o agendamento no banco de dados.
     */
    public function save(Schedule $schedule): void
    {
        $schedule->save();
    }

    /**
     * Verifica se existem Records ativos vinculados ao agendamento.
     */
    public function hasActiveRecords(int $scheduleId): bool
    {
        return Record::active()->where('schedule_id', $scheduleId)->exists();
    }

    /**
     * Localiza um Ticket pelo ID. Retorna null quando não encontrado.
     */
    public function findTicket(int $ticketId): ?Ticket
    {
        return Ticket::query()->find($ticketId);
    }
}
