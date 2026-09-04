<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\MonitorRepositoryInterface;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MonitorRepository implements MonitorRepositoryInterface
{
    /**
     * Retorna agentes ativos com subqueries de contagem para o painel de monitoramento.
     * Exclui departamentos 3 e 4 (departamentos não operacionais).
     * Usa withCount e withMax para disparar UMA única query no banco.
     */
    public function getAgentsWithStats(Carbon $start, Carbon $end, Carbon $now): Collection
    {
        return Agent::active()
            ->whereNotIn('department_id', [3, 4])
            ->withCount([
                'tickets as pending' => fn ($q) => $q->pendingStatus()
                    ->whereBetween('created_at', [$start, $end]),
                'tickets as status' => fn ($q) => $q->where('updated_at', '>', $now->copy()->subSeconds(30))
                    ->where('status_id', '!=', 3),
                'schedules as schedules_morning' => fn ($q) => $q->whereBetween(
                    'start_at',
                    [$start->copy()->startOfDay(), $start->copy()->setTime(12, 0)]
                ),
                'schedules as schedules_afternoon' => fn ($q) => $q->whereBetween(
                    'start_at',
                    [$start->copy()->setTime(12, 1), $end->copy()->endOfDay()]
                ),
            ])
            ->withMax(
                ['tickets as last_completed' => fn ($q) => $q->whereBetween('completed_at', [$start, $end])],
                'completed_at'
            )
            ->get()
            ->sortByDesc('last_completed')
            ->values();
    }

    /**
     * Busca apenas os horários (created_at) dos tickets no período,
     * formatados como 'H:i:s' para filtragem em memória sem nova query.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getTicketTimesInPeriod(Carbon $start, Carbon $end): \Illuminate\Support\Collection
    {
        return Ticket::whereBetween('created_at', [$start, $end])
            ->pluck('created_at')
            ->map(fn ($date) => Carbon::parse($date)->format('H:i:s'));
    }
}
