<?php

namespace App\Models\Ticket;

use App\Models\User;
use App\Models\Department;
use App\Models\Schedule;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends User
{
    
    protected $table = 'users'; 

    protected $searchable = [
        'columns' => [
            'users.name' => 30,
        ],
    ];

    /**
     * Escopo: apenas agentes ativos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    
    /**
     * Escopo para filtrar apenas usuários que são Agentes.
     */
    public function scopeAgents(Builder $query, int|bool $paginate = false): mixed
    {
        $query->where('ticket_agent', true);

        return $paginate ? $query->paginate($paginate, ['*'], 'agents_page') : $query;
    }

    /**
     * Escopo para filtrar apenas usuários que são Admins.
     */
    public function scopeAdmins(Builder $query, int|bool $paginate = false): mixed
    {
        $query->where('ticket_admin', true);

        return $paginate ? $query->paginate($paginate, ['*'], 'admins_page') : $query;
    }

    /**
     * Retorna lista de agentes [id => name].
     */
    public function scopeAgentsLists(Builder $query): array
    {
        return $query->where('ticket_agent', true)->pluck('name', 'id')->toArray();
    }

    // RELACIONAMENTOS
   
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'agent_id');
    }
}