<?php

namespace App\Models\Helpdesk\Scopes;

use Illuminate\Database\Eloquent\Builder;

trait TicketScopes
{
    /**
     * Tickets completos (tem completed_at).
     */
    public function scopeComplete(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Tickets ativos (não completos).
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    /**
     * Tickets por usuário.
     */
    public function scopeUserTickets(Builder $query, int $id): Builder
    {
        return $query->where('user_id', $id);
    }

    /**
     * Tickets por agente.
     */
    public function scopeAgentTickets(Builder $query, int $id): Builder
    {
        return $query->where('agent_id', $id);
    }

    /**
     * Tickets onde a pessoa é agente ou usuário.
     */
    public function scopeAgentUserTickets(Builder $query, int $id): Builder
    {
        return $query->where(function (Builder $q) use ($id) {
            $q->where('agent_id', $id)
              ->orWhere('user_id', $id);
        });
    }
}
