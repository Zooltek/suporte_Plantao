<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface ReportRepositoryInterface
{
    public function getAgentsWithTicketCounts(Carbon $start, Carbon $end): Collection;

    public function countTicketsWithoutAgent(Carbon $start, Carbon $end): array;

    public function getTicketStatsByCompany(Carbon $start, Carbon $end): Collection;

    public function getImplementationClientIds(?Carbon $start, ?Carbon $end): array;

    public function getClientsWithCounts(array $clientIds, ?Carbon $start, ?Carbon $end): Collection;

    public function getScheduleActiveCount(int $clientId, ?Carbon $start, ?Carbon $end): int;

    public function getImplementationMinutes(int $clientId, ?Carbon $start, ?Carbon $end): int;

    public function getTicketStatsByProblem(?Carbon $start, ?Carbon $end, ?int $softwareId = null): Collection;

    public function getClientsWithoutAttendance(?Carbon $start = null, ?Carbon $end = null): Collection;

    public function getClientUpdates(?int $softwareId = null): Collection;

    public function getDashboardTickets(): Collection;

    public function getDashboardSchedules(): Collection;
}

