<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\MonitorRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MonitorService
{
    public function __construct(
        private readonly MonitorRepositoryInterface $repository
    ) {}

    /**
     * Busca os agentes e suas estatísticas via Repository (UMA única query com Subqueries).
     */
    public function getAgentsData(Carbon $start, Carbon $end, Carbon $now): Collection
    {
        return $this->repository->getAgentsWithStats($start, $end, $now);
    }

    /**
     * Gera os dados do gráfico a partir dos horários buscados pelo Repository.
     * A filtragem por intervalos de 30 min ocorre em memória (Collection).
     */
    public function getChartData(Carbon $start, Carbon $end, Carbon $now): array
    {
        $chartData = ['labels' => [], 'data' => []];
        $tempTime  = $now->copy()->setTime(8, 30);

        $ticketTimes = $this->repository->getTicketTimesInPeriod($start, $end);

        for ($i = 0; $i < 20; $i++) {
            $intervalStart = $tempTime->format('H:i:s');
            $nextInterval  = $tempTime->copy()->addMinutes(30);
            $intervalEnd   = $nextInterval->format('H:i:s');

            $chartData['labels'][] = $tempTime->format('H:i');

            $count = $ticketTimes->filter(
                fn ($time) => $time >= $intervalStart && $time < $intervalEnd
            )->count();

            $chartData['data'][] = $count;
            $tempTime = $nextInterval;
        }

        return $chartData;
    }
}
