<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\TicketQueryRepositoryInterface;
use App\Http\Requests\Agent\Tickets\TicketIndexRequest;
use App\Models\Department;
use App\Services\Access\AccessService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Responsabilidade única: lógica de listagem/filtragem de tickets.
 *
 * Separado do TicketService (save/update) para seguir o Princípio da Responsabilidade Única (SRP).
 */
class TicketQueryService
{
    private const PER_PAGE = 15;

    public function __construct(
        private readonly AccessService $accessService,
        private readonly TicketQueryRepositoryInterface $ticketQueryRepository,
    ) {}

    /**
     * Retorna a paginação de tickets de acordo com os filtros e o papel do usuário.
     */
    public function listForUser(Authenticatable $user, TicketIndexRequest $request): LengthAwarePaginator
    {
        $viewMode = $this->resolveViewMode($user, $request);
        $isScopedView = $viewMode['isMineView'] || $viewMode['isUnassignedView'];

        $dateFrom = $this->resolveDateFrom($isScopedView, $request);
        $dateTo = $this->resolveDateTo($isScopedView, $request);

        $filters = [
            'q' => $request->q,
            'status' => $request->status ? (int) $request->status : null,
            'category' => $request->category ? (int) $request->category : null,
            'company' => $request->company ? (int) $request->company : null,
            'origin' => $request->origin ? (int) $request->origin : null,
            'unassigned' => $viewMode['isUnassignedView'],
            'agent' => ($viewMode['isMineView'] || $viewMode['isUnassignedView'])
                ? null
                : ($request->agent ?: null),
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];

        // Admin pode escolher um departamento específico via filtro;
        // agentes não-admin sempre são limitados ao próprio setor.
        $departmentScope = $viewMode['isAdmin'] && $request->department
            ? (int) $request->department
            : $viewMode['departmentScope'];

        return $this->ticketQueryRepository->paginateTickets(
            $filters,
            $viewMode['agentScope'],
            $departmentScope,
            $viewMode['includeDepartmentQueue'],
            (int) $request->order,
            self::PER_PAGE,
        );
    }

    /**
     * Retorna os dados auxiliares necessários para a view de filtros.
     */
    public function getFilterData(Authenticatable $user, TicketIndexRequest $request): array
    {
        $viewMode = $this->resolveViewMode($user, $request);
        $isAdmin = $viewMode['isAdmin'];
        $isAgent = (bool) $user->ticketit_agent;

        $isScopedView = $viewMode['isMineView'] || $viewMode['isUnassignedView'];

        $dateFrom = $this->resolveDateFrom($isScopedView, $request);
        $dateTo = $this->resolveDateTo($isScopedView, $request);

        return [
            'statuses' => $this->ticketQueryRepository->getAllStatuses(),
            'categories' => $this->ticketQueryRepository->getCategoryFilterOptions(),
            'companies' => ($isAdmin || $isAgent)
                ? $this->ticketQueryRepository->getAllCompanies()
                : collect(),
            'agents' => ($isAdmin && ! $viewMode['isMineView'] && ! $viewMode['isUnassignedView'])
                ? $this->ticketQueryRepository->getActiveAgents()
                : collect(),
            'origins' => $this->ticketQueryRepository->getAllOrigins(),
            'departments' => $isAdmin
                ? Department::query()->orderBy('name')->get(['id', 'name'])
                : collect(),
            'isAdmin' => $isAdmin,
            'isMineView' => $viewMode['isMineView'],
            'isUnassignedView' => $viewMode['isUnassignedView'],
            'currentAgentName' => (string) ($user->name ?? 'Você'),
            'dateFrom' => $dateFrom ?: '',
            'dateTo' => $dateTo ?: '',
        ];
    }

    private function resolveDateFrom(bool $isScopedView, TicketIndexRequest $request): ?string
    {
        if ($request->filled('date_from')) {
            return (string) $request->input('date_from');
        }

        if ($isScopedView) {
            return now()->toDateString();
        }

        return null;
    }

    private function resolveDateTo(bool $isScopedView, TicketIndexRequest $request): ?string
    {
        if ($request->filled('date_to')) {
            return (string) $request->input('date_to');
        }

        if ($isScopedView) {
            return now()->toDateString();
        }

        return null;
    }

    /**
     * @return array{agentScope:int|null,departmentScope:int|null,includeDepartmentQueue:bool,isAdmin:bool,isMineView:bool,isUnassignedView:bool}
     */
    private function resolveViewMode(Authenticatable $user, TicketIndexRequest $request): array
    {
        $isAdmin = $this->accessService->isAdmin($user);
        $isUnassignedView = $request->boolean('unassigned') || $request->agent === 'unassigned' || $request->agent === '0';
        $isMineView = ! $isAdmin
            ? (! $isUnassignedView)
            : ($request->boolean('mine') && ! $isUnassignedView);
        $departmentScope = $isAdmin ? null : (int) ($user->department_id ?? 0);

        return [
            'agentScope' => $isMineView ? (int) $user->id : null,
            'departmentScope' => $departmentScope ?: null,
            'includeDepartmentQueue' => false,
            'isAdmin' => $isAdmin,
            'isMineView' => $isMineView,
            'isUnassignedView' => $isUnassignedView,
        ];
    }
}
