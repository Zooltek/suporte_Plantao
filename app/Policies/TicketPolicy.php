<?php

namespace App\Policies;

use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Access\AccessService;

/**
 * Controla acesso a Tickets — previne IDOR (OWASP A01).
 *
 * Regras de negócio:
 *  - viewAny: qualquer agente autenticado pode listar.
 *  - view: agentes podem visualizar; chamados encerrados há mais de um dia
 *          bloqueiam usuários comuns (apenas atendentes especiais têm acesso).
 *  - create: qualquer agente.
 *  - update: agente responsável (agent_id), autor (author_id) ou admin;
 *            chamados encerrados obedecem à mesma restrição de view.
 *  - delete: somente admin.
 */
class TicketPolicy
{
    public function __construct(private readonly AccessService $accessService) {}

    public function viewAny(User $user): bool
    {
        return $user->isAgent();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if (! $user->isAgent()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        if (! $this->sameDepartment($user, $ticket)) {
            return false;
        }

        // Todos os agentes do departamento têm acesso para visualizar chamados anteriores e fechados
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAgent();
    }

    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Chamados de dias anteriores ou encerrados só podem ser editados por administradores
        if ($this->isPastOrClosedTicket($ticket)) {
            return false;
        }

        if (! $this->sameDepartment($user, $ticket)) {
            return false;
        }

        return $user->isAgent() && (
            (int) $ticket->agent_id === $user->id ||
            (int) $ticket->author_id === $user->id
        );
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Apenas Admin pode alterar o departamento responsável de um ticket.
     */
    public function changeDepartment(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    /**
     * Retorna true se o ticket foi criado em data anterior a hoje ou já está encerrado.
     */
    public function isPastOrClosedTicket(Ticket $ticket): bool
    {
        $isClosed = Status::isTerminal((int) $ticket->status_id) || $ticket->completed_at !== null;
        $isPast = $ticket->created_at && $ticket->created_at->lt(today());

        return $isClosed || $isPast;
    }

    private function sameDepartment(User $user, Ticket $ticket): bool
    {
        if ($ticket->department_id === null) {
            return true;
        }

        return (int) $ticket->department_id === (int) $user->department_id;
    }
}
