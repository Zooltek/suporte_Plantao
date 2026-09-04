<?php

namespace App\Models\Helpdesk\Ticket;

use App\Models\User;
use App\Models\Company;
use App\Models\Helpdesk\Ticket;
use App\Models\Helpdesk\Ticket\Category;
use App\Models\Helpdesk\Setting;
use Illuminate\Support\Facades\Auth;

class Agent extends User
{
    protected $table = 'users';

    /** Escopo: lista de agentes */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function agents($query, $paginate = false)
    {
        return $paginate
            ? $query->where('ticketit_agent', 1)->paginate($paginate, ['*'], 'agents_page')
            : $query->where('ticketit_agent', 1)->get();
    }

    /** Escopo: lista de admins */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function admins($query, $paginate = false)
    {
        return $paginate
            ? $query->where('ticketit_admin', 1)->paginate($paginate, ['*'], 'admins_page')
            : $query->where('ticketit_admin', 1)->get();
    }

    /** Escopo: lista de usuários comuns */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function users($query, $paginate = false)
    {
        return $paginate
            ? $query->where('ticketit_agent', 0)->paginate($paginate, ['*'], 'users_page')
            : $query->where('ticketit_agent', 0)->get();
    }

    /** Lista de agentes (id => nome) */
    #[\Illuminate\Database\Eloquent\Attributes\Scope]
    protected function agentsLists($query)
    {
        return $query->where('ticketit_agent', 1)->pluck('name', 'id')->toArray();
    }

    /** Verifica se é agente */
    public function isAgent($id = null): bool
    {
        $user = $id ? User::find($id) : Auth::user();
        return $user?->ticketit_agent === 1;
    }

    /** Verifica se é admin */
    public function isAdmin(): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        if ($user->ticketit_admin) return true;

        $adminIds = Setting::grab('admin_ids') ?? [];
        return in_array($user->id, $adminIds);
    }

    /** Verifica se é agente atribuído ao ticket */
    public static function isAssignedAgent(int $id): bool
    {
        $user = Auth::user();
        if ($user?->ticketit_agent) {
            $ticket = Ticket::find($id);
            return $ticket && ($ticket->agent_id == 0 || $user->id == $ticket->agent_id);
        }
        return false;
    }

    /** Verifica se é dono do ticket */
    public static function isTicketOwner(int $id): bool
    {
        $user = Auth::user();
        $ticket = Ticket::find($id);
        return $user && $ticket && $user->id === $ticket->user_id;
    }

    /** Verifica se está na lista de conversa */
    public static function isOnConversationList(int $id): bool
    {
        $user = Auth::user();
        if (!$user) return false;

        $conversation = Ticket::find($id, ['conversation_id_list']);
        $ids = explode(',', (string) $conversation?->conversation_id_list);

        return in_array($user->id, $ids);
    }

    /** Relação com categorias */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'ticketit_categories_users', 'user_id', 'category_id');
    }

    /** Tickets atribuídos ao agente */
    public function agentTickets($complete = false)
    {
        return $this->hasMany(Ticket::class, 'agent_id')
            ->when($complete, fn($q) => $q->whereNotNull('completed_at'))
            ->when(!$complete, fn($q) => $q->whereNull('completed_at'));
    }

    /** Tickets do usuário */
    public function userTickets($complete = false)
    {
        return $this->hasMany(Ticket::class, 'user_id')
            ->when($complete, fn($q) => $q->whereNotNull('completed_at'))
            ->when(!$complete, fn($q) => $q->whereNull('completed_at'));
    }

    /** Todos tickets (admin/agent/user) */
    public function getTickets($complete = false)
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return Ticket::when($complete, fn($q) => $q->whereNotNull('completed_at'))
                         ->when(!$complete, fn($q) => $q->whereNull('completed_at'));
        }

        if ($user->isAgent()) {
            return $user->agentTickets($complete);
        }

        return $user->userTickets($complete);
    }
}
