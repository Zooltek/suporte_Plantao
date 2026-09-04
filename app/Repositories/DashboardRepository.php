<?php

namespace App\Repositories;

use App\Contracts\Repositories\DashboardRepositoryInterface;
use App\Models\CategoryDescription;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Tasks\Notification as TaskNotification;
use App\Models\Tasks\Task;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function getCustomers(): Collection
    {
        return Customer::orderBy('trade_name', 'asc')->get();
    }

    public function countSchedules(?int $agentId, string $type): int
    {
        $pending = ['pen', 'sch', 'con'];
        $query = Schedule::active()->whereIn('status', $pending);

        if ($agentId !== null) {
            $query->where('agent_id', $agentId);
        }

        return match ($type) {
            'today' => $query->whereDate('start_at', Carbon::today())->count(),
            'overdue' => $query->whereDate('start_at', '<', Carbon::today())->count(),
            'upcoming' => $query->whereBetween('start_at', [
                Carbon::tomorrow()->startOfDay(),
                Carbon::now()->addDays(7)->endOfDay(),
            ])->count(),
            default => 0,
        };
    }

    public function getRecentAttendances(?int $agentId): Collection
    {
        $query = Attendance::query()
            ->with(['ticket.company', 'user', 'returnAssignee'])
            ->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())
            ->orderBy('created_at', 'asc')
            ->limit(60);

        if ($agentId !== null) {
            $query->where('user_id', $agentId);
        }

        return $query->get();
    }

    public function countTodayAttendances(?int $agentId): int
    {
        $query = Attendance::query()->whereDate('created_at', Carbon::today());

        if ($agentId !== null) {
            $query->where('user_id', $agentId);
        }

        return $query->count();
    }

    public function getRecentTickets(?int $agentId, ?int $departmentId = null, bool $isAdmin = true): Collection
    {
        $query = Ticket::query()
            ->withSlaDependencies()
            ->orderByRaw('CASE WHEN status_id = 5 THEN 3 WHEN status_id = 3 THEN 2 ELSE 1 END')
            ->orderByDesc('created_at')
            ->with(['company', 'status', 'agent'])
            ->where(function ($q) {
                $q->whereNull('completed_at')
                    ->orWhere('completed_at', '>', Carbon::now()->subDay()->endOfDay());
            });

        if (! $isAdmin && $departmentId) {
            $query->where('department_id', $departmentId);
        }

        $query->where(function (Builder $builder) use ($agentId, $departmentId, $isAdmin) {
            if ($agentId !== null) {
                $builder
                    ->where('agent_id', $agentId)
                    ->orWhere(function (Builder $queueBuilder) use ($departmentId, $isAdmin) {
                        $queueBuilder->queuePending();

                        if (! $isAdmin && $departmentId) {
                            $queueBuilder->where('department_id', $departmentId);
                        }
                    });

                return;
            }

            $builder
                ->withAssignedAgent()
                ->orWhere(fn (Builder $queueBuilder) => $queueBuilder->queuePending());
        });

        return $query->get();
    }

    public function getCategoryNames(): Collection
    {
        return CategoryDescription::pluck('name', 'category_id');
    }

    public function getSchedulesForWeek(Carbon $startOfWeek, Carbon $endOfWeek, ?int $agentId): Collection
    {
        $query = Schedule::active()
            ->whereBetween('start_at', [$startOfWeek->copy()->startOfDay(), $endOfWeek])
            ->orderByRaw("CASE WHEN status = 'pen' THEN 1 WHEN status = 'con' THEN 2 WHEN status = 'sch' THEN 3 ELSE 4 END")
            ->orderBy('start_at', 'asc')
            ->with(['customer:id,trade_name', 'agent:id,name', 'module:id,name', 'ticket:id,subject']);

        if ($agentId !== null) {
            $query->where('agent_id', $agentId);
        }

        return $query->get();
    }

    public function countMyTasks(int $userId, string $status): int
    {
        return Task::visible()
            ->where('status', $status)
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('tester_id', $userId)
                    ->orWhere('author_id', $userId);
            })->count();
    }

    /**
     * Conta tarefas com data de entrega = hoje em que o usuário é responsável,
     * testador ou autor, e que ainda não foram concluídas/canceladas.
     */
    public function countMyTasksDueToday(int $userId): int
    {
        return Task::active()
            ->whereDate('delivery_at', Carbon::today())
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                    ->orWhere('tester_id', $userId)
                    ->orWhere('author_id', $userId);
            })->count();
    }

    public function countUnseenTaskNotifications(int $userId): int
    {
        return TaskNotification::where('user_id', $userId)->where('seen', 0)->count();
    }

    public function getUserSettings(int $userId): array
    {
        return Setting::where('user_id', $userId)->pluck('value', 'slug')->toArray();
    }
}
