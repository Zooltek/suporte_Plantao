<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): bool
    {
        return (bool) $user->delete();
    }

    public function anonymize(User $user): void
    {
        $user->anonymize();
    }

    public function allDepartments(): Collection
    {
        return Department::all();
    }

    public function getDeletionPreviewData(User $user): array
    {
        $activeTicketsCount = \Illuminate\Support\Facades\DB::table('ticketit')
            ->where('agent_id', $user->id)
            ->whereNull('completed_at')
            ->count();

        $closedTicketsCount = \Illuminate\Support\Facades\DB::table('ticketit')
            ->where('agent_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        $pendingSchedulesCount = \Illuminate\Support\Facades\DB::table('schedule')
            ->where('agent_id', $user->id)
            ->where('status', '!=', 'fin')
            ->count();

        $activeTasksCount = \Illuminate\Support\Facades\DB::table('tasks')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'fin')
            ->count();

        $eligibleAgents = User::query()
            ->where('id', '!=', $user->id)
            ->where('active', 1)
            ->where(function ($q) {
                $q->where('ticketit_agent', 1)
                  ->orWhere('ticketit_admin', 1);
            })
            ->orderByRaw('id = 1 DESC, name ASC')
            ->get(['id', 'name', 'email', 'ticketit_admin']);

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'active_tickets_count' => $activeTicketsCount,
            'closed_tickets_count' => $closedTicketsCount,
            'pending_schedules_count' => $pendingSchedulesCount,
            'active_tasks_count' => $activeTasksCount,
            'total_active_items' => $activeTicketsCount + $pendingSchedulesCount + $activeTasksCount,
            'eligible_agents' => $eligibleAgents,
            'default_transfer_agent_id' => $eligibleAgents->first()?->id,
        ];
    }

    public function reassignActiveRecords(User $fromUser, User $toUser): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($fromUser, $toUser) {
            $now = now();
            $actorId = auth('admin')->id() ?? auth()->id() ?? $toUser->id;

            // 1. Reatribuir chamados ativos
            $activeTicketIds = \Illuminate\Support\Facades\DB::table('ticketit')
                ->where('agent_id', $fromUser->id)
                ->whereNull('completed_at')
                ->pluck('id');

            if ($activeTicketIds->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::table('ticketit')
                    ->whereIn('id', $activeTicketIds)
                    ->update([
                        'agent_id' => $toUser->id,
                        'updated_at' => $now,
                    ]);

                $audits = [];
                foreach ($activeTicketIds as $ticketId) {
                    $audits[] = [
                        'ticket_id' => $ticketId,
                        'user_id' => $actorId,
                        'event' => 'agent_changed',
                        'operation' => 'transfer_on_user_delete',
                        'field' => 'agent_id',
                        'old_value' => $fromUser->name,
                        'new_value' => $toUser->name,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                \Illuminate\Support\Facades\DB::table('ticketit_audits')->insert($audits);
            }

            // 2. Reatribuir agendamentos pendentes
            \Illuminate\Support\Facades\DB::table('schedule')
                ->where('agent_id', $fromUser->id)
                ->where('status', '!=', 'fin')
                ->update([
                    'agent_id' => $toUser->id,
                    'updated_at' => $now,
                ]);

            // 3. Reatribuir tarefas ativas
            \Illuminate\Support\Facades\DB::table('tasks')
                ->where('user_id', $fromUser->id)
                ->where('status', '!=', 'fin')
                ->update([
                    'user_id' => $toUser->id,
                    'updated_at' => $now,
                ]);
        });
    }
}
