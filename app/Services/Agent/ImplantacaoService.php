<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\ImplantacaoRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ImplantacaoService
{
    public function __construct(
        private readonly ImplantacaoRepositoryInterface $repository,
    ) {}

    /**
     * KPIs gerais do módulo de implantação.
     *
     * @return array{
     *     totalClients: int,
     *     totalSchedules: int,
     *     totalOpenTickets: int,
     *     totalMinutes: int,
     *     totalHoursFormatted: string
     * }
     */
    public function getStats(): array
    {
        $clientIds = $this->repository->getImplementationClientIds();

        $totalClients        = count(array_unique($clientIds));
        $totalSchedules      = $this->repository->countActiveSchedules();
        $totalOpenTickets    = $this->repository->getOpenTicketsCount($clientIds);
        $totalMinutes        = $this->repository->getTotalMinutes($clientIds);
        $totalHoursFormatted = $this->formatMinutes($totalMinutes);

        return compact(
            'totalClients',
            'totalSchedules',
            'totalOpenTickets',
            'totalMinutes',
            'totalHoursFormatted',
        );
    }

    /**
     * Clientes em implantação com contadores agregados e tempo total gasto.
     */
    public function getClients(): Collection
    {
        $clientIds = $this->repository->getImplementationClientIds();

        $clients = $this->repository->getClientsWithDetails($clientIds);

        return $clients->map(function ($client) {
            $client->total_hours_formatted = $this->formatMinutes($client->total_minutes);
            return $client;
        })->values();
    }

    /**
     * Agendamentos ativos de implantação (não finalizados/cancelados), mais recentes primeiro.
     */
    public function getSchedules(int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->getSchedulesPaginated($perPage);
    }

    /**
     * Formata uma quantidade de minutos em string legível (ex: "2h 05min" ou "30min").
     */
    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }

        $h   = intdiv($minutes, 60);
        $min = $minutes % 60;

        return $h > 0
            ? sprintf('%dh %02dmin', $h, $min)
            : sprintf('%dmin', $min);
    }
}
