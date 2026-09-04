<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AgentRepositoryInterface;
use App\Models\Ticket\Agent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AgentRepository implements AgentRepositoryInterface
{
    /**
     * Lista agentes com contagem de tickets dos últimos 7 dias.
     * Usa eager loading do departamento para evitar N+1.
     */
    public function listAgents(bool $showAll, ?string $query = null): Collection
    {
        return Agent::query()
            ->select(['id', 'name', 'email', 'department_id'])
            ->when(!$showAll, fn (Builder $q) => $q->where('ticketit_agent', 1))
            ->when($query, fn (Builder $q) => $q->search($query))
            ->active()
            ->with('department:id,name')
            ->withCount(['tickets as last_tickets_count' => function (Builder $q) {
                $q->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()]);
            }])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Agentes do departamento 1 com média e contagem de avaliações (ratings),
     * calculados diretamente no banco para evitar iterações PHP.
     * Nota: User não possui scopeActive; filtra por active=true diretamente.
     */
    public function getAgentRatings(): Collection
    {
        return User::query()
            ->where('active', true)
            ->where('department_id', 1)
            ->withAvg('ratings as rating_avg', 'rating')
            ->withCount('ratings as rating_count')
            ->get();
    }

    /**
     * Agentes aniversariantes do dia, excluindo o usuário informado.
     */
    public function getBirthdays(int $excludeUserId): Collection
    {
        return Agent::query()
            ->whereDay('birthday', Carbon::now()->day)
            ->whereMonth('birthday', Carbon::now()->month)
            ->where('id', '!=', $excludeUserId)
            ->get();
    }
}
