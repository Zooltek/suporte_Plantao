<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\TicketShowRepositoryInterface;
use App\Models\Category;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Services\Access\AccessService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;

/**
 * Responsabilidade única: leitura e exibição de um ticket específico.
 *
 * SRP: separado do TicketService (save) e do TicketQueryService (lista).
 */
class TicketShowService
{
    public function __construct(
        private readonly AccessService $accessService,
        private readonly TicketShowRepositoryInterface $repository
    ) {}

    /**
     * Carrega um ticket com todos os dados necessários para exibição.
     */
    public function loadForDisplay(Ticket $ticket): Ticket
    {
        return $this->repository->loadForDisplay($ticket);
    }

    /**
     * Retorna os comentários do ticket, ordenados cronologicamente.
     */
    public function getComments(Ticket $ticket): Collection
    {
        return $this->repository->getComments($ticket->id);
    }

    /**
     * Retorna os anexos ativos do ticket.
     */
    public function getAttachments(Ticket $ticket): Collection
    {
        return $this->repository->getAttachments($ticket->id);
    }

    /**
     * Retorna os dados de contexto: statuses e agentes disponíveis.
     */
    public function getContextData(Authenticatable $user): array
    {
        $isAdmin = $this->accessService->isAdmin($user);
        $statuses = $this->repository->getAllStatuses();

        return [
            'statuses' => $statuses,
            'quickStatuses' => Status::quickUpdateOptions(),
            'closeStatuses' => Status::closeWorkflowOptions(),
            'agentsList' => $this->repository->getAgentsList(),
            'departmentsList' => $isAdmin
                ? Department::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'categoriesList' => Category::query()
                ->with('description')
                ->root()
                ->orderedByDisplayName()
                ->get(),
            'isAdmin' => $isAdmin,
            'isAgent' => (bool) $user->ticketit_agent,
        ];
    }
}
