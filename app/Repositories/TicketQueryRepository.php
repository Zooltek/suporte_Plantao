<?php

namespace App\Repositories;

use App\Contracts\Repositories\TicketQueryRepositoryInterface;
use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Agent;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TicketQueryRepository implements TicketQueryRepositoryInterface
{
    /**
     * Executa a query paginada de tickets com eager loading, filtros e ordenação.
     *
     * @param  array{q?: string, status?: int, category?: int, company?: int, agent?: int, origin?: int}  $filters
     * @param  int|null  $agentScope  Quando não-null, inclui tickets do agente e fila do setor.
     * @param  int  $order  1=última atualização, 2=mais recentes, default=prioridade+tempo
     */
    public function paginateTickets(
        array $filters,
        ?int $agentScope,
        ?int $departmentScope,
        bool $includeDepartmentQueue,
        int $order,
        int $perPage,
    ): LengthAwarePaginator {
        $query = Ticket::with([
            'status',
            'category.description',
            'subCategory.description',
            'agent',
            'company',
            'origin',
            'department',
        ])
            ->withSlaDependencies();

        if ($agentScope !== null) {
            $query->where(function ($scope) use ($agentScope, $departmentScope, $includeDepartmentQueue) {
                $scope->where('ticketit.agent_id', $agentScope);

                if ($includeDepartmentQueue && $departmentScope !== null) {
                    $scope->orWhere(function ($queue) use ($departmentScope) {
                        $queue->queuePending()
                            ->where('ticketit.department_id', $departmentScope);
                    });
                }
            });
        }

        if ($departmentScope !== null) {
            $query->where(function ($scope) use ($departmentScope) {
                $scope
                    ->where('ticketit.department_id', $departmentScope)
                    ->orWhereNull('ticketit.department_id');
            });
        }

        $query
            ->when($filters['q'] ?? null, fn ($q, $v) => $q->where(fn ($s) => $s
                ->where('ticketit.subject', 'like', "%{$v}%")
                ->orWhere('ticketit.contact', 'like', "%{$v}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('ticketit.status_id', $v))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->where('ticketit.category_id', $v))
            ->when($filters['company'] ?? null, fn ($q, $v) => $q->where('ticketit.company_id', $v))
            ->when($filters['unassigned'] ?? false, fn ($q) => $q->withoutAssignedAgent())
            ->when(! ($filters['unassigned'] ?? false) ? ($filters['agent'] ?? null) : null, function ($q, $v) {
                if ($v === 'unassigned' || $v === 0 || $v === '0') {
                    $q->withoutAssignedAgent();
                } else {
                    $q->where('ticketit.agent_id', (int) $v);
                }
            })
            ->when($filters['origin'] ?? null, fn ($q, $v) => $q->where('ticketit.origin_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->where('ticketit.created_at', '>=', \Illuminate\Support\Carbon::parse($v)->startOfDay()))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->where('ticketit.created_at', '<=', \Illuminate\Support\Carbon::parse($v)->endOfDay()));

        $this->applySorting($query, $order);

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * Retorna todos os status ordenados por nome (para o seletor de filtro).
     */
    public function getAllStatuses(): Collection
    {
        return Status::orderedDistinctForSelection();
    }

    /**
     * Retorna categorias raiz ordenadas pelo nome visível ao usuário.
     */
    public function getCategoryFilterOptions(): Collection
    {
        return Category::query()
            ->with('description')
            ->root()
            ->orderedByDisplayName()
            ->get();
    }

    /**
     * Retorna todas as empresas ordenadas por trade_name.
     */
    public function getAllCompanies(): Collection
    {
        return Company::orderBy('trade_name')->get();
    }

    /**
     * Retorna agentes ativos (ticketit_agent = 1) ordenados por nome.
     */
    public function getActiveAgents(): Collection
    {
        return Agent::where('ticketit_agent', 1)->orderBy('name')->get();
    }

    public function getAllOrigins(): Collection
    {
        return Origin::query()->orderBy('name')->get();
    }

    // ── Sorting ────────────────────────────────────────────────────────────────

    /**
     * Aplica a estratégia de ordenação ao query.
     *
     * Hierarquia de status:
     * 1. Pendentes
     * 2. Abertos (ordenados pela regra selecionada: prioridade, atualização ou recentes)
     * 3. Em andamento
     * 4. Não resolvidos
     * 5. Solicitações
     * 6. Visita técnica
     * 7. Chamados finalizados (resolvidos / terminais)
     *
     * order=1 → última atualização (desc)
     * order=2 → mais recentes (created_at desc)
     * default / order=3 → prioridade + tempo (peso desc, created_at asc)
     */
    private function applySorting(\Illuminate\Database\Eloquent\Builder $query, int $order): void
    {
        $query
            ->select('ticketit.*')
            ->leftJoin('ticketit_statuses as _status_ord', 'ticketit.status_id', '=', '_status_ord.id');

        $pendingId = TicketStatusCatalog::PENDING_ID;
        $openId = TicketStatusCatalog::OPEN_ID;
        $inProgressId = TicketStatusCatalog::IN_PROGRESS_ID;
        $unresolvedId = TicketStatusCatalog::UNRESOLVED_ID;
        $requestId = TicketStatusCatalog::REQUEST_ID;
        $technicalVisitId = TicketStatusCatalog::TECHNICAL_VISIT_ID;
        $resolvedId = TicketStatusCatalog::RESOLVED_ID;

        $statusHierarchySql = "
            CASE
                WHEN ticketit.status_id = {$pendingId} THEN 1
                WHEN ticketit.status_id = {$openId} THEN 2
                WHEN ticketit.status_id = {$inProgressId} THEN 3
                WHEN ticketit.status_id = {$unresolvedId} THEN 4
                WHEN ticketit.status_id = {$requestId} THEN 5
                WHEN ticketit.status_id = {$technicalVisitId} THEN 6
                WHEN COALESCE(_status_ord.is_terminal, 0) = 1 OR ticketit.status_id = {$resolvedId} THEN 7
                ELSE 6.5
            END ASC
        ";

        $query->orderByRaw($statusHierarchySql);

        if ($order === 1) {
            $query->orderBy('ticketit.updated_at', 'desc');

            return;
        }

        if ($order === 2) {
            $query->orderBy('ticketit.created_at', 'desc');

            return;
        }

        $this->applyPriorityTimeSort($query);
    }

    /**
     * Ordenação composta: peso de prioridade DESC + idade do chamado ASC.
     *
     * O peso é calculado via JOIN com solutions_category (categoria + subcategoria),
     * usando pesos definidos em Category::PRIORITY_WEIGHTS.
     * Tickets críticos e mais antigos sobem ao topo da timeline.
     */
    private function applyPriorityTimeSort(\Illuminate\Database\Eloquent\Builder $query): void
    {
        $query
            ->leftJoin('solutions_category as _cat_pw', 'ticketit.category_id', '=', '_cat_pw.category_id')
            ->leftJoin('solutions_category as _sub_pw', 'ticketit.sub_category_id', '=', '_sub_pw.category_id')
            ->orderByRaw("
                (CASE _cat_pw.priority WHEN 'urgent' THEN 5 WHEN 'high' THEN 3 WHEN 'low' THEN 1 ELSE 0 END
                + CASE _sub_pw.priority WHEN 'urgent' THEN 5 WHEN 'high' THEN 3 WHEN 'low' THEN 1 ELSE 0 END) DESC
            ")
            ->orderBy('ticketit.created_at', 'asc');
    }
}
