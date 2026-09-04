<?php

namespace App\Services\Report;

use App\Contracts\Helpdesk\Ticketit\SlaServiceInterface;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Gera dados consolidados de chamados agrupados por departamento.
 *
 * Cada linha do relatório contém: contagens por status (aberto/em andamento/
 * resolvido), distribuição por faixa de SLA dos abertos, tempo médio de
 * atendimento dos resolvidos e top categorias do período.
 *
 * Mantido como Service puro (sem acesso a request) para ser facilmente
 * testável e reutilizado em CLI/export.
 */
class DepartmentReportService
{
    public function __construct(
        private readonly SlaServiceInterface $slaService,
    ) {}

    /**
     * @return Collection<int, array{
     *     department_id: ?int,
     *     department_name: string,
     *     total: int,
     *     open: int,
     *     in_progress: int,
     *     resolved: int,
     *     sla: array{normal:int, attention:int, warning:int, critical:int},
     *     avg_resolution_minutes: ?int,
     *     top_categories: array<int, array{name:string, count:int}>,
     * }>
     */
    public function buildReport(?Carbon $from = null, ?Carbon $to = null): Collection
    {
        $from = $from ?? now()->subDays(30)->startOfDay();
        $to = $to ?? now()->endOfDay();

        $terminalStatusIds = Status::query()
            ->where('is_terminal', true)
            ->pluck('id')
            ->all();

        $tickets = Ticket::query()
            ->with(['status:id,name,is_terminal', 'category.description', 'subCategory.description'])
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $departments = Department::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->keyBy('id');

        $grouped = $tickets->groupBy(fn (Ticket $t) => $t->department_id);

        return $grouped
            ->map(fn (Collection $items, $departmentId) => $this->summarizeDepartment(
                $departmentId !== '' ? (int) $departmentId : null,
                $items,
                $departments,
                $terminalStatusIds,
            ))
            ->values()
            ->sortByDesc('total')
            ->values();
    }

    /**
     * @param  Collection<int, Ticket>  $items
     * @param  array<int>  $terminalStatusIds
     */
    private function summarizeDepartment(
        ?int $departmentId,
        Collection $items,
        Collection $departments,
        array $terminalStatusIds,
    ): array {
        $departmentName = $departmentId !== null
            ? ($departments[$departmentId]->name ?? '— Removido')
            : '— Sem departamento';

        $open = $items->filter(fn (Ticket $t) => (int) $t->status_id === Ticket::STATUS_PENDING_ID)->count();
        $inProgress = $items->filter(fn (Ticket $t) => (int) $t->status_id === Ticket::STATUS_IN_PROGRESS_ID)->count();
        $resolved = $items->filter(fn (Ticket $t) => in_array((int) $t->status_id, $terminalStatusIds, true))->count();

        return [
            'department_id' => $departmentId,
            'department_name' => $departmentName,
            'total' => $items->count(),
            'open' => $open,
            'in_progress' => $inProgress,
            'resolved' => $resolved,
            'sla' => $this->summarizeSlaForOpen($items, $terminalStatusIds),
            'avg_resolution_minutes' => $this->avgResolutionMinutes($items, $terminalStatusIds),
            'top_categories' => $this->topCategories($items),
        ];
    }

    /**
     * @param  Collection<int, Ticket>  $items
     * @param  array<int>  $terminalStatusIds
     * @return array{normal:int, attention:int, warning:int, critical:int}
     */
    private function summarizeSlaForOpen(Collection $items, array $terminalStatusIds): array
    {
        $buckets = ['normal' => 0, 'attention' => 0, 'warning' => 0, 'critical' => 0];

        foreach ($items as $ticket) {
            if (in_array((int) $ticket->status_id, $terminalStatusIds, true)) {
                continue;
            }

            $level = $this->slaService->resolveSlaLevel($ticket);

            if (isset($buckets[$level])) {
                $buckets[$level]++;
            }
        }

        return $buckets;
    }

    /**
     * @param  Collection<int, Ticket>  $items
     * @param  array<int>  $terminalStatusIds
     */
    private function avgResolutionMinutes(Collection $items, array $terminalStatusIds): ?int
    {
        $minutes = $items
            ->filter(fn (Ticket $t) => in_array((int) $t->status_id, $terminalStatusIds, true)
                && $t->completed_at
                && $t->created_at)
            ->map(fn (Ticket $t) => Carbon::parse($t->created_at)->diffInMinutes(Carbon::parse($t->completed_at)));

        if ($minutes->isEmpty()) {
            return null;
        }

        return (int) round($minutes->avg());
    }

    /**
     * @param  Collection<int, Ticket>  $items
     * @return array<int, array{name:string, count:int}>
     */
    private function topCategories(Collection $items): array
    {
        return $items
            ->groupBy(fn (Ticket $t) => $t->category?->display_name ?? 'Sem categoria')
            ->map(fn (Collection $rows, $name) => ['name' => (string) $name, 'count' => $rows->count()])
            ->sortByDesc('count')
            ->values()
            ->take(5)
            ->all();
    }
}
