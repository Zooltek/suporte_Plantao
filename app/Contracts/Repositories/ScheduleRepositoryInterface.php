<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Models\Ticket\Ticket;

interface ScheduleRepositoryInterface
{
    /**
     * Persiste (INSERT ou UPDATE) um agendamento.
     */
    public function save(Schedule $schedule): void;

    /**
     * Verifica se existem Records ativos vinculados ao agendamento.
     */
    public function hasActiveRecords(int $scheduleId): bool;

    /**
     * Localiza um Ticket pelo ID para uso como origem do agendamento.
     * Retorna null se não encontrado.
     */
    public function findTicket(int $ticketId): ?Ticket;
}
