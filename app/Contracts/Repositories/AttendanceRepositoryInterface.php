<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Collection;

interface AttendanceRepositoryInterface
{
    /**
     * Busca um ticket pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function findTicketOrFail(int $ticketId): Ticket;

    /**
     * Retorna os atendimentos de um ticket com eager loading de usuários,
     * ordenados do mais recente para o mais antigo.
     */
    public function listForTicket(int $ticketId): Collection;

    /**
     * Persiste um novo Attendance no banco.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Attendance;
}
