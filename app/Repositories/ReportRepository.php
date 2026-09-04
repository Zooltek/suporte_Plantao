<?php

namespace App\Repositories;

use App\Contracts\Repositories\ReportRepositoryInterface;
use App\Models\Company;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;
use App\Models\Schedule\Record;


class ReportRepository implements ReportRepositoryInterface
{
    /**
     * Retorna agentes ativos com contagens de tickets (pendentes e concluídos) no período.
     */
    public function getAgentsWithTicketCounts(Carbon $start, Carbon $end): Collection
    {
        return Agent::active()
            ->where('ticketit_agent', 1)
            ->withCount([
                'tickets as pendings_total' => fn ($q) => $q->pendingStatus(),
                'tickets as pendings_date' => fn ($q) => $q->pendingStatus()
                    ->whereBetween('created_at', [$start, $end]),
                'tickets as completed' => fn ($q) => $q->whereBetween('completed_at', [$start, $end]),
            ])
            ->get();
    }

    /**
     * Retorna contagens de tickets sem agente atribuído.
     */
    public function countTicketsWithoutAgent(Carbon $start, Carbon $end): array
    {
        return [
            'pendings_total' => Ticket::query()
                ->queuePending()
                ->count(),

            'pendings_date' => Ticket::query()
                ->queuePending()
                ->whereBetween('created_at', [$start, $end])
                ->count(),

            'completed' => Ticket::query()
                ->withoutAssignedAgent()
                ->where('status_id', 3)
                ->whereBetween('completed_at', [now()->subDay(), now()])
                ->count(),
        ];
    }

    /**
     * Retorna estatísticas de tickets agrupadas por empresa no período.
     */
    public function getTicketStatsByCompany(Carbon $start, Carbon $end): Collection
    {
        $query = Ticket::with('company:id,trade_name')
            ->select('company_id')
            ->selectRaw('SUM(CASE WHEN created_at < ? AND status_id != 3 THEN 1 ELSE 0 END) as pendings', [$start])
            ->selectRaw('SUM(CASE WHEN created_at BETWEEN ? AND ? AND status_id != 3 THEN 1 ELSE 0 END) as pendings_date', [$start, $end])
            ->selectRaw('SUM(CASE WHEN completed_at BETWEEN ? AND ? AND status_id = 3 THEN 1 ELSE 0 END) as completed', [$start, $end]);

        return $this->applyRelevantTicketWindow($query, $start, $end)
            ->groupBy('company_id')
            ->get();
    }

    /**
     * Retorna array com IDs únicos de clientes que possuem agendamentos ativos ou tickets abertos no período.
     */
    public function getImplementationClientIds(?Carbon $start, ?Carbon $end): array
    {
        $schedulesQuery = DB::table('schedule_record')->where('status', 1);
        $ticketsQuery = Ticket::where('status_id', '!=', 3);

        $this->applyDateBounds($schedulesQuery, 'start', $start, $end);
        $this->applyDateBounds($ticketsQuery, 'created_at', $start, $end);

        $clientsWithSchedules = $schedulesQuery->pluck('customer_id')->toArray();
        $clientsWithOpenTickets = $ticketsQuery->pluck('company_id')->toArray();

        return array_unique(array_merge($clientsWithSchedules, $clientsWithOpenTickets));
    }

    /**
     * Retorna empresas com eager loading e contagem de tickets abertos.
     */
    public function getClientsWithCounts(array $clientIds, ?Carbon $start, ?Carbon $end): Collection
    {
        return Company::whereIn('id', $clientIds)
            ->with(['state', 'software'])
            ->withCount([
                'tickets as open_tickets' => function ($q) use ($start, $end) {
                    $q->where('status_id', '!=', 3);
                    $this->applyDateBounds($q, 'created_at', $start, $end);
                },
            ])
            ->get();
    }

    /**
     * Retorna a contagem de agendamentos ativos de um cliente no período.
     */
    public function getScheduleActiveCount(int $clientId, ?Carbon $start, ?Carbon $end): int
    {
        $query = DB::table('schedule_record')
            ->where('customer_id', $clientId)
            ->where('status', 1);

        $this->applyDateBounds($query, 'start', $start, $end);

        return $query->count();
    }

    /**
     * Retorna o total de minutos de implementação de um cliente no período,
     * descontando os intervalos registrados.
     *
     * ATENÇÃO: Utiliza TIMESTAMPDIFF (MySQL/MariaDB). Não compatível com SQLite.
     */
    public function getImplementationMinutes(int $clientId, ?Carbon $start, ?Carbon $end): int
    {
        $query = DB::table('schedule_record')
            ->where('customer_id', $clientId)
            ->whereNotNull('start')
            ->whereNotNull('end');

        $this->applyDateBounds($query, 'start', $start, $end);

        if (DB::connection()->getDriverName() === 'sqlite') {
            return (int) $query->get(['start', 'end', 'interval_start', 'interval_end'])
                ->sum(function ($record) {
                    $minutes = Carbon::parse($record->start)->diffInMinutes(Carbon::parse($record->end));

                    if ($record->interval_start && $record->interval_end) {
                        $minutes -= Carbon::parse($record->interval_start)
                            ->diffInMinutes(Carbon::parse($record->interval_end));
                    }

                    return max(0, $minutes);
                });
        }

        return (int) $query
            ->selectRaw('SUM(TIMESTAMPDIFF(MINUTE, start, `end`) - COALESCE(TIMESTAMPDIFF(MINUTE, interval_start, interval_end), 0)) as total_minutes')
            ->value('total_minutes');
    }

    /**
     * Retorna estatísticas de tickets agrupadas por subcategoria (problema) no período.
     */
    public function getTicketStatsByProblem(?Carbon $start, ?Carbon $end, ?int $softwareId = null): Collection
    {
        $query = Ticket::query()
            ->select('ticketit.sub_category_id')
            ->selectRaw('COALESCE(scd.name, "Não classificado") as problem_name')
            ->leftJoin('customers as companies', 'companies.id', '=', 'ticketit.company_id')
            ->leftJoin('solutions_category_description as scd', 'scd.category_id', '=', 'ticketit.sub_category_id')
            ->whereNotNull('ticketit.sub_category_id');

        if ($softwareId) {
            $query->where('companies.software_id', $softwareId);
        }

        $this->applyProblemSelects($query, $start, $end);

        return $this->applyRelevantProblemWindow($query, $start, $end)
            ->groupBy('ticketit.sub_category_id', 'scd.name')
            ->get();
    }

    private function applyDateBounds(
        EloquentBuilder|QueryBuilder $query,
        string $column,
        ?Carbon $start,
        ?Carbon $end,
    ): EloquentBuilder|QueryBuilder {
        if ($start) {
            $query->where($column, '>=', $start);
        }

        if ($end) {
            $query->where($column, '<=', $end);
        }

        return $query;
    }

    private function applyRelevantTicketWindow(
        EloquentBuilder $query,
        Carbon $start,
        Carbon $end,
    ): EloquentBuilder {
        return $query->where(function ($outerQuery) use ($start, $end) {
            $outerQuery
                ->where(function ($openTicketsQuery) use ($start, $end) {
                    $openTicketsQuery
                        ->where('ticketit.status_id', '!=', 3)
                        ->where(function ($datesQuery) use ($start, $end) {
                            $datesQuery
                                ->whereBetween('ticketit.created_at', [$start, $end])
                                ->orWhere('ticketit.created_at', '<', $start);
                        });
                })
                ->orWhere(function ($completedTicketsQuery) use ($start, $end) {
                    $completedTicketsQuery
                        ->where('ticketit.status_id', 3)
                        ->whereBetween('ticketit.completed_at', [$start, $end]);
                });
        });
    }

    private function applyProblemSelects(
        EloquentBuilder $query,
        ?Carbon $start,
        ?Carbon $end,
    ): void {
        if ($start) {
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id != 3 AND ticketit.created_at < ? THEN 1 ELSE 0 END) as pendings',
                [$start]
            );
        } else {
            $query->selectRaw('0 as pendings');
        }

        if ($start && $end) {
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id != 3 AND ticketit.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as pendings_date',
                [$start, $end]
            );
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id = 3 AND ticketit.completed_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as completed',
                [$start, $end]
            );

            return;
        }

        if ($start) {
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id != 3 AND ticketit.created_at >= ? THEN 1 ELSE 0 END) as pendings_date',
                [$start]
            );
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id = 3 AND ticketit.completed_at >= ? THEN 1 ELSE 0 END) as completed',
                [$start]
            );

            return;
        }

        if ($end) {
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id != 3 AND ticketit.created_at <= ? THEN 1 ELSE 0 END) as pendings_date',
                [$end]
            );
            $query->selectRaw(
                'SUM(CASE WHEN ticketit.status_id = 3 AND ticketit.completed_at <= ? THEN 1 ELSE 0 END) as completed',
                [$end]
            );

            return;
        }

        $query->selectRaw('SUM(CASE WHEN ticketit.status_id != 3 THEN 1 ELSE 0 END) as pendings_date');
        $query->selectRaw('SUM(CASE WHEN ticketit.status_id = 3 THEN 1 ELSE 0 END) as completed');
    }

    private function applyRelevantProblemWindow(
        EloquentBuilder $query,
        ?Carbon $start,
        ?Carbon $end,
    ): EloquentBuilder {
        if (! $start && ! $end) {
            return $query;
        }

        return $query->where(function (EloquentBuilder $outerQuery) use ($start, $end) {
            $outerQuery
                ->where(function (EloquentBuilder $openTicketsQuery) use ($start, $end) {
                    $openTicketsQuery->where('ticketit.status_id', '!=', 3);

                    if ($start && $end) {
                        $openTicketsQuery->where(function (EloquentBuilder $datesQuery) use ($start, $end) {
                            $datesQuery
                                ->whereBetween('ticketit.created_at', [$start, $end])
                                ->orWhere('ticketit.created_at', '<', $start);
                        });

                        return;
                    }

                    if ($start) {
                        return;
                    }

                    if ($end) {
                        $openTicketsQuery->where('ticketit.created_at', '<=', $end);
                    }
                })
                ->orWhere(function (EloquentBuilder $completedTicketsQuery) use ($start, $end) {
                    $completedTicketsQuery->where('ticketit.status_id', 3);

                    if ($start && $end) {
                        $completedTicketsQuery->whereBetween('ticketit.completed_at', [$start, $end]);

                        return;
                    }

                    if ($start) {
                        $completedTicketsQuery->where('ticketit.completed_at', '>=', $start);

                        return;
                    }

                    if ($end) {
                        $completedTicketsQuery->where('ticketit.completed_at', '<=', $end);
                    }
                });
        });
    }

    public function getClientsWithoutAttendance(?Carbon $start = null, ?Carbon $end = null): Collection
    {
        return Company::query()
            ->where('is_active', true)
            ->whereDoesntHave('tickets', function (EloquentBuilder $query) use ($start, $end) {
                if ($start) {
                    $query->where('created_at', '>=', $start);
                }
                if ($end) {
                    $query->where('created_at', '<=', $end);
                }
            })
            ->orderBy('name')
            ->get();
    }

    public function getClientUpdates(?int $softwareId = null): Collection
    {
        $query = Company::query()
            ->where('is_active', true)
            ->where('financial_irregular', false)
            ->with(['group', 'software']);

        if ($softwareId) {
            $query->where('software_id', $softwareId);
        }

        return $query->orderBy('name')->get();
    }

    public function getDashboardTickets(): Collection
    {
        return Ticket::query()
            ->whereHas('status', function ($query) {
                $query->where('is_terminal', false)
                    ->where('requires_agent', false);
            })
            ->withoutAssignedAgent()
            ->withSlaDependencies()
            ->with(['company', 'agent', 'department', 'priority', 'status', 'origin'])
            ->orderByDesc('priority_id')
            ->orderBy('created_at')
            ->get();
    }

    public function getDashboardSchedules(): Collection
    {
        return Schedule::query()
            ->active()
            ->where('status', '!=', 'can')
            ->whereDate('start_at', Carbon::today())
            ->with(['customer', 'agent', 'module', 'scheduleType', 'ticket'])
            ->orderBy('start_at')
            ->get();
    }
}

