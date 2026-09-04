<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Services\Access\AccessService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    public function __construct(
        private readonly AccessService $accessService,
        private readonly DashboardRepositoryInterface $dashboardRepository,
    ) {}

    /**
     * Retorna os dados para a tela inicial do Dashboard.
     */
    public function getIndexData(array $filters): array
    {
        $user = Auth::guard('admin')->user();
        $agentId = $this->agentScope($user);

        return [
            'start' => data_get($filters, 'start') ? Carbon::parse($filters['start']) : Carbon::now(),
            'settings' => $this->dashboardRepository->getUserSettings((int) $user?->id),
            'customers' => $this->dashboardRepository->getCustomers(),
            'schedules_today' => $this->dashboardRepository->countSchedules($agentId, 'today'),
            'schedules_overdue' => $this->dashboardRepository->countSchedules($agentId, 'overdue'),
            'schedules_upcoming' => $this->dashboardRepository->countSchedules($agentId, 'upcoming'),
            'tasks_new' => $this->countMyTasks($user, 'new'),
            'tasks_stopped' => $this->countMyTasks($user, 'sto'),
            'tasks_today' => $this->countMyTasksDueToday((int) $user?->id),
            'tasks_notifications' => $this->countUnseenTaskNotifications((int) $user?->id),
        ];
    }

    /**
     * Conta tarefas com data de entrega hoje em que o usuário está envolvido.
     */
    public function countMyTasksDueToday(int $userId): int
    {
        if (! $userId) {
            return 0;
        }

        return $this->dashboardRepository->countMyTasksDueToday($userId);
    }

    /**
     * Retorna os dados complexos para a visualização condensada do calendário.
     */
    public function getCondensedData($user, array $filters): array
    {
        $start = data_get($filters, 'start') ? Carbon::parse($filters['start']) : Carbon::now();
        $rawAgentId = data_get($filters, 'agent_id');
        $requestedAgentId = $rawAgentId !== null ? (int) $rawAgentId : null;
        $scheduleAgentId = $this->resolveAgentId($user, $requestedAgentId);
        $ticketAgentId = $this->resolveTicketAgentId($requestedAgentId);
        $tickets = $this->buildTicketsCollection($user, $ticketAgentId);
        $calendarResult = $this->buildSchedulesCalendar($start, $user, $scheduleAgentId);

        return [
            'tickets' => $tickets,
            'schedules_count' => $calendarResult['count'],
            'schedules_data' => $calendarResult['data'],
            'start' => $start,
            'active' => data_get($filters, 'active'),
            'settings' => $this->dashboardRepository->getUserSettings((int) $user?->id),
            'attendances' => $this->dashboardRepository->getRecentAttendances($this->agentScope($user)),
            'attendances_today_count' => $this->dashboardRepository->countTodayAttendances($this->agentScope($user)),
        ];
    }

    /**
     * Retorna a estrutura do calendário semanal de agendamentos.
     * Consumido pela API V1 (frontend Blade) e pela visão condensada.
     */
    public function getSchedulesCalendar(Carbon $start, $user = null, ?int $agentId = null): array
    {
        $resolvedAgentId = $agentId ?? $this->agentScope($user);

        return $this->buildSchedulesCalendar($start, $user, $resolvedAgentId);
    }

    /**
     * Conta tarefas do usuário com o status informado.
     */
    public function countMyTasks($user, string $status): int
    {
        if (! $user) {
            return 0;
        }

        return $this->dashboardRepository->countMyTasks($user->id, $status);
    }

    /**
     * Conta notificações de tarefas não lidas do usuário.
     */
    public function countUnseenTaskNotifications(int $userId): int
    {
        if (! $userId) {
            return 0;
        }

        return $this->dashboardRepository->countUnseenTaskNotifications($userId);
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    /**
     * Retorna o agent_id de escopo quando o usuário é agente mas não é admin.
     * Retorna null quando o usuário é admin (acessa todos os registros).
     */
    private function agentScope($user): ?int
    {
        if ($this->accessService->isAgent($user) && ! $this->accessService->isAdmin($user)) {
            return $user?->id;
        }

        return null;
    }

    /**
     * Resolve o agent_id levando em conta o filtro explícito ou o escopo de papel.
     *
     * agent_id=0  → "Mostrar Tudo": remove o filtro de agente mesmo para não-admins.
     * agent_id=N  → filtra pelo agente N.
     * agent_id ausente → aplica escopo padrão (próprio agente para não-admins).
     */
    private function resolveAgentId($user, ?int $requestedAgentId): ?int
    {
        // 0 é o sinal explícito de "sem filtro" enviado pelo botão Mostrar Tudo
        if ($requestedAgentId === 0) {
            return null;
        }

        if ($requestedAgentId) {
            return $requestedAgentId;
        }

        return $this->agentScope($user);
    }

    /**
     * Tickets da visão condensada são globais por padrão.
     * O filtro explícito por agent_id continua sendo respeitado.
     */
    private function resolveTicketAgentId(?int $requestedAgentId): ?int
    {
        return $requestedAgentId ?: null;
    }

    /**
     * Busca tickets do repositório e aplica formatação de nomes (categoria e empresa).
     */
    private function buildTicketsCollection($user, ?int $agentId): Collection
    {
        $tickets = $this->dashboardRepository->getRecentTickets(
            $agentId,
            $this->accessService->isAdmin($user) ? null : (int) ($user->department_id ?? 0),
            $this->accessService->isAdmin($user),
        );
        $categories = $this->dashboardRepository->getCategoryNames();

        $tickets->each(function ($ticket) use ($categories) {
            if ($ticket->category_id && $ticket->sub_category_id) {
                $catName = $categories->get($ticket->category_id, '');
                $subCatName = $categories->get($ticket->sub_category_id, '');
                $ticket->category_name = trim("{$catName} - {$subCatName}", ' -');
            }

            $ticket->company_name = $ticket->company?->trade_name;
        });

        return $tickets;
    }

    /**
     * Monta a estrutura complexa do calendário semanal (Segunda a Sexta).
     */
    private function buildSchedulesCalendar(Carbon $startDate, $user, ?int $agentId = null): array
    {
        // Força início na Segunda-feira (ISO), evitando que locales pt_BR
        // retornem Domingo como primeiro dia e omitam a Sexta-feira.
        $startOfWeek = $startDate->copy()->startOfWeek(Carbon::MONDAY);
        $endOfWeek = $startOfWeek->copy()->addDays(6)->endOfDay();

        $schedules = $this->dashboardRepository->getSchedulesForWeek($startOfWeek, $endOfWeek, $agentId);

        $data = [];
        $tempDate = $startOfWeek->copy();

        // Inicializa os 5 dias úteis (Seg-Sex)
        for ($i = 0; $i < 5; $i++) {
            $dateKey = $tempDate->format('d-m-Y');
            $data[$dateKey] = [
                'day' => $tempDate->format('d - M'),
                'day_week' => $this->getWeekDayName($tempDate->dayOfWeekIso),
                'day_object' => $tempDate->copy(),
                'today' => $tempDate->isToday(),
                'morning' => array_fill(0, 4, null),
                'afternoon' => array_fill(0, 4, null),
                'morning_count' => 4,
                'afternoon_count' => 4,
                'morning_remaining' => 4,
                'afternoon_remaining' => 4,
                'last_index_morning' => 0,
                'last_index_afternoon' => 0,
                'max' => 4,
            ];
            $tempDate->addDay();
        }

        // Preenche os slots
        foreach ($schedules as $schedule) {
            $dateKey = $schedule->start_at->format('d-m-Y');

            if (isset($data[$dateKey])) {
                $isMorning = $schedule->start_at->hour < 12;
                $period = $isMorning ? 'morning' : 'afternoon';
                $lastIndex = $data[$dateKey]["last_index_{$period}"];

                $data[$dateKey][$period][$lastIndex] = $schedule;
                $data[$dateKey]["{$period}_remaining"]--;
                $data[$dateKey]["last_index_{$period}"]++;

                if ($data[$dateKey]["{$period}_remaining"] <= 0) {
                    $data[$dateKey]["{$period}_count"]++;
                }

                $data[$dateKey]['max'] = max(
                    $data[$dateKey]['morning_count'],
                    $data[$dateKey]['afternoon_count']
                );
            }
        }

        return ['data' => $data, 'count' => $schedules->count()];
    }

    private function getWeekDayName(int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'seg', 2 => 'ter', 3 => 'qua',
            4 => 'qui', 5 => 'sex', 6 => 'sáb',
            7 => 'dom', default => '???',
        };
    }
}
