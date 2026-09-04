<?php

namespace App\Models\Helpdesk\Ticketit;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Models\Ticket\Ticket;

class Agent extends User
{
    protected $table = 'users';

    /**
     * Scopes agora retornam apenas a Query (Builder), permitindo encadeamento.
     */
    public function scopeAgents(Builder $query, mixed $paginate = false): mixed
    {
        $builder = $query->where('ticketit_agent', '1');
        return $paginate ? $builder->paginate($paginate, ['*'], 'agents_page') : $builder;
    }

    public function scopeAdmins(Builder $query, mixed $paginate = false): mixed
    {
        $builder = $query->where('ticketit_admin', '1');
        return $paginate ? $builder->paginate($paginate, ['*'], 'admins_page') : $builder;
    }

    public function scopeUsers(Builder $query, mixed $paginate = false): mixed
    {
        $builder = $query->where('ticketit_agent', '0');
        return $paginate ? $builder->paginate($paginate, ['*'], 'users_page') : $builder;
    }

    /**
     * S6600 & Obsoleto: Substituído lists() por pluck().
     */
    public function scopeAgentsLists(Builder $query): array
    {
        return $query->where('ticketit_agent', '1')->pluck('name', 'id')->toArray();
    }

    public function isAgent(?int $id = null): bool
    {
        if ($id !== null) {
            $user = User::find($id);
            return (bool) ($user?->ticketit_agent);
        }

        return (bool) (Auth::user()?->ticketit_agent);
    }

    public function isAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->ticketit_admin) {
            return true;
        }

        $adminIds = Setting::grab('admin_ids');
        return is_array($adminIds) && in_array($user->id, $adminIds);
    }

    /**
     * Relações devidamente tipadas para Laravel 12.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            Category::class,
            'ticketit_categories_users',
            'user_id',
            'category_id'
        );
    }

    public function agentTotalTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'agent_id');
    }

    public function agentOpenTickets(): HasMany
    {
        return $this->agentTotalTickets()->whereNull('completed_at');
    }

    public function userTotalTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'user_id');
    }

    /**
     * S3776: Simplificação da lógica de verificação de lista de conversão.
     */
    public static function isOnConversationList(int $ticketId): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        $ticket = Ticket::find($ticketId, ['conversation_id_list']);
        if (!$ticket || !$ticket->conversation_id_list) {
            return false;
        }

        $list = explode(',', (string) $ticket->conversation_id_list);
        return in_array((string) $user->id, $list, true);
    }
}
