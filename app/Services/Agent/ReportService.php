<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Enums\Reports\ImplementationClientSituation;
use Illuminate\Support\Carbon;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\Agent;

class ReportService
{
    public function __construct(
        private readonly ReportRepositoryInterface $reportRepository,
    ) {}

    /**
     * Gera relatório de suporte agrupado por departamento e agente.
     */
    public function generateSuporteData(Carbon $start, Carbon $end): array
    {
        $agents = $this->reportRepository->getAgentsWithTicketCounts($start, $end);
        $noAgent = $this->reportRepository->countTicketsWithoutAgent($start, $end);

        $data = [];
        $totals = ['pendings' => 0, 'pendings_date' => 0, 'completed' => 0, 'total' => 0];

        foreach ($agents as $agent) {
            $row = [
                'agent' => $agent,
                'pendings' => $agent->pendings_total - $agent->pendings_date,
                'pendings_date' => $agent->pendings_date,
                'completed' => $agent->completed,
                'total' => $agent->pendings_total + $agent->completed,
            ];

            $data[$agent->department_id][] = $row;

            $totals['pendings'] += $row['pendings'];
            $totals['pendings_date'] += $row['pendings_date'];
            $totals['completed'] += $row['completed'];
            $totals['total'] += $row['total'];
        }

        if (isset($data[1])) {
            $data[1] = collect($data[1])
                ->sortByDesc(fn ($item) => [$item['completed'], $item['total']])
                ->values()
                ->all();
        }

        $data[1][] = [
            'agent' => (object) ['name' => 'Sem Atendimento'],
            'pendings' => $noAgent['pendings_total'] - $noAgent['pendings_date'],
            'pendings_date' => $noAgent['pendings_date'],
            'completed' => $noAgent['completed'],
            'total' => $noAgent['pendings_total'] + $noAgent['completed'],
        ];

        $data[1][] = array_merge(['agent' => (object) ['name' => 'Total']], $totals);

        return $data;
    }

    /**
     * Gera relatório de clientes delegando o agrupamento para o Banco de Dados.
     */
    public function generateClientesData(Carbon $start, Carbon $end): array
    {
        $stats = $this->reportRepository->getTicketStatsByCompany($start, $end);

        $data = [];
        $totals = ['pendings' => 0, 'pendings_date' => 0, 'completed' => 0, 'total' => 0, 'name' => 'Total'];

        foreach ($stats as $stat) {
            $total = $stat->pendings + $stat->pendings_date + $stat->completed;

            $data[] = [
                'name' => $stat->company?->trade_name ?? 'N/A',
                'pendings' => (int) $stat->pendings,
                'pendings_date' => (int) $stat->pendings_date,
                'completed' => (int) $stat->completed,
                'total' => $total,
            ];

            $totals['pendings'] += $stat->pendings;
            $totals['pendings_date'] += $stat->pendings_date;
            $totals['completed'] += $stat->completed;
            $totals['total'] += $total;
        }

        $sortedData = collect($data)->sortByDesc(fn ($item) => [$item['total'], $item['completed']])->toArray();
        $sortedData['total'] = $totals;

        return $sortedData;
    }

    /**
     * Relatório de clientes em implantação.
     */
    public function getImplementationClientsData(
        ?Carbon $start = null,
        ?Carbon $end = null,
        ImplementationClientSituation $situation = ImplementationClientSituation::ALL,
    ): array {
        $clientIds = $this->reportRepository->getImplementationClientIds($start, $end);

        $clients = $this->reportRepository
            ->getClientsWithCounts($clientIds, $start, $end)
            ->map(function ($client) use ($start, $end) {
                $client->active_schedules = $this->reportRepository
                    ->getScheduleActiveCount($client->id, $start, $end);

                $client->total_implementation_minutes = $this->reportRepository
                    ->getImplementationMinutes($client->id, $start, $end);

                return $client;
            })
            ->filter(fn ($client) => $situation->matches($client))
            ->sortBy('name');

        $totalMinutes = $clients->sum('total_implementation_minutes');

        return [
            'clients' => $clients,
            'totalClients' => $clients->count(),
            'totalOpenTickets' => $clients->sum('open_tickets'),
            'totalSchedules' => $clients->sum('active_schedules'),
            'totalImplementationMinutes' => $totalMinutes,
            'totalImplementationFormatted' => $this->formatMinutes($totalMinutes),
        ];
    }

    /**
     * Relatório diário de chamados agrupados por Problema (subcategoria).
     */
    public function generateProblemasData(?Carbon $start, ?Carbon $end, ?int $softwareId = null): array
    {
        $stats = $this->reportRepository->getTicketStatsByProblem($start, $end, $softwareId);

        $data = [];

        foreach ($stats as $stat) {
            $total = (int) $stat->pendings + (int) $stat->pendings_date + (int) $stat->completed;

            $data[] = [
                'name' => $stat->problem_name ?? 'Não classificado',
                'pendings' => (int) $stat->pendings,
                'pendings_date' => (int) $stat->pendings_date,
                'completed' => (int) $stat->completed,
                'total' => $total,
            ];
        }

        return collect($data)
            ->sortByDesc(fn ($item) => [$item['total'], $item['completed']])
            ->values()
            ->all();
    }

    private function formatMinutes(int $minutes): string
    {
        if ($minutes <= 0) {
            return '—';
        }

        $h = intdiv($minutes, 60);
        $min = $minutes % 60;

        return $h > 0 ? sprintf('%dh %02dmin', $h, $min) : sprintf('%dmin', $min);
    }

    public function getClientsWithoutAttendanceData(?Carbon $start = null, ?Carbon $end = null): array
    {
        return [
            'clients' => $this->reportRepository->getClientsWithoutAttendance($start, $end),
        ];
    }

    public function getClientUpdatesData(?int $softwareId = null)
    {
        return $this->reportRepository->getClientUpdates($softwareId);
    }

    public function getDashboardTvData(): array
    {
        $openTickets = $this->reportRepository->getDashboardTickets();
        $schedules = $this->reportRepository->getDashboardSchedules();

        $supportDeptId = \DB::table('user_department')->where('name', 'like', '%Suporte%')->value('id') ?? 1;
        $supportOpenTickets = $openTickets->filter(fn($t) => $t->department_id == $supportDeptId);

        $closedTodayCount = Ticket::whereHas('status', fn($q) => $q->where('is_terminal', true))
            ->whereDate('completed_at', Carbon::today())
            ->count();

        // Usuários logados/ativos: calcula a quantidade de usuários únicos com sessão ativa
        $activeUsersCount = $this->getActiveUsersCount();

        $slaOverdueCount = $openTickets->filter(fn($t) => $t->sla_level === 'critical')->count();

        // Map open tickets waiting time
        $formattedTickets = $openTickets->map(function ($ticket) {
            $minutes = (int) Carbon::parse($ticket->created_at)->diffInMinutes(Carbon::now());
            $h = intdiv($minutes, 60);
            $m = $minutes % 60;
            $ticket->time_waiting = $h > 0 ? "{$h}h {$m}min" : "{$m}min";
            return $ticket;
        });

        // Formata os agendamentos para o dashboard TV
        $formattedSchedules = $schedules->map(function ($schedule) {
            $schedule->start = $schedule->start_at?->toIso8601String();
            $schedule->formatted_title = $schedule->title 
                ?: ($schedule->scheduleType?->label ?? $schedule->module?->name ?? 'Agendamento');
            return $schedule;
        });

        return [
            'kpis' => [
                'open_tickets_count' => $supportOpenTickets->count(),
                'closed_tickets_count' => $closedTodayCount,
                'sla_overdue_count' => $slaOverdueCount,
                'active_techs_count' => $activeUsersCount,
                'active_users_count' => $activeUsersCount,
            ],
            'tickets' => $formattedTickets,
            'schedules' => $formattedSchedules,
        ];
    }

    /**
     * Calcula a quantidade de usuários únicos com sessões ativas recentes.
     * Combina rastreamento em Cache (tempo real) com fallbacks para Banco, Redis e Arquivo.
     */
    public function getActiveUsersCount(): int
    {
        // 1. Tracker em tempo real no Cache (fonte autoritativa atualizada por login/logout/middleware)
        try {
            $trackerRegistry = \Illuminate\Support\Facades\Cache::get(\App\Services\Auth\UserOnlineTracker::REGISTRY_KEY);
            if (is_array($trackerRegistry)) {
                $cutoff = now()->subMinutes(5)->timestamp;
                $activeCount = 0;
                foreach ($trackerRegistry as $uid => $timestamp) {
                    if ($timestamp >= $cutoff) {
                        $activeCount++;
                    }
                }
                return $activeCount;
            }
        } catch (\Throwable) {
            // Falha silenciosa
        }

        // 2. Se o registro ainda não foi inicializado (cold start), faz fallback para Redis/Banco
        $activeUserIds = [];

        // Fallback: Redis se configurado
        try {
            if (config('session.driver') === 'redis' || config('database.redis.session')) {
                $redis = \Illuminate\Support\Facades\Redis::connection('session');
                $keys = $redis->keys('*');
                foreach ($keys as $key) {
                    $val = $redis->get($key);
                    if ($val) {
                        if (preg_match('/login_(?:web|admin)_[a-f0-9]+["\']?;\s*(?:i:|s:\d+:["\']?)(\d+)/', $val, $m)) {
                            $activeUserIds[$m[1]] = true;
                        }
                    }
                }
            }
        } catch (\Throwable) {}

        // Fallback: banco de dados se session.driver === 'database'
        if (empty($activeUserIds) && config('session.driver') === 'database' && \Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            try {
                $cutoffTimestamp = Carbon::now()->subMinutes(5)->timestamp;
                $dbUserIds = \Illuminate\Support\Facades\DB::table('sessions')
                    ->whereNotNull('user_id')
                    ->where('last_activity', '>=', $cutoffTimestamp)
                    ->pluck('user_id')
                    ->all();

                foreach ($dbUserIds as $uid) {
                    if ($uid) {
                        $activeUserIds[$uid] = true;
                    }
                }
            } catch (\Throwable) {}
        }

        return count($activeUserIds);
    }
}


