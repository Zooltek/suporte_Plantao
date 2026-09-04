<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use DomainException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CompanyService
{
    public function __construct(
        private readonly CompanyRepositoryInterface $companyRepository,
    ) {}

    /**
     * Obtém o histórico consolidado de uma empresa (Tickets, Pendências e Agendamentos).
     *
     * Regras de negócio orquestradas aqui; dados buscados via Repository.
     * Filtros suportados (via $filters):
     *   start, end  → período (created_at)
     *   agent_id    → agente responsável pelo ticket
     *   category_id → categoria do ticket
     *   take, mode  → uso legado (AJAX/condensed) — mantidos para compatibilidade
     */
    public function getCompanyHistory(Company $company, array $filters): array
    {
        $mode = data_get($filters, 'mode', false);
        $currentTicketId = data_get($filters, 'id');

        // 1. Tickets com filtros — Repository decide entre paginate e limit
        if ($mode) {
            $take = (int) data_get($filters, 'take', 10);
            $tickets = $this->companyRepository->limitCompanyTickets($company, $filters, $take);
            $hasMore = $tickets->count() >= $take;
        } else {
            $tickets = $this->companyRepository->paginateCompanyTickets($company, $filters);
            $hasMore = false;
        }

        // 2. IDs de status terminais (cached no Repository)
        $terminalIds = $this->companyRepository->getTerminalStatusIds();

        // 3. Regra de negócio da fila de pendências:
        //    — considera apenas chamados pendentes sem responsável
        //    — ignora o ticket atual (já em edição) para não criar falso alerta
        $pendings = $this->companyRepository->getPendingTickets($company, $terminalIds);
        $firstPending = $pendings->first();
        $hasPending = $firstPending !== null && $currentTicketId != $firstPending->id;

        if (! $hasPending) {
            $pendings = collect();
        }

        // 4. Últimos finalizados e Agendamentos
        $finalizedTickets = $this->companyRepository->getFinalizedTickets($company, $terminalIds, limit: 5);
        $schedules = $this->companyRepository->getCompanySchedules($company);

        return [
            'company' => $company,
            'tickets' => $tickets,
            'finalized_tickets' => $finalizedTickets,
            'pending' => $hasPending,
            'pendings' => $pendings,
            'schedules' => $schedules,
            'has_more' => $hasMore,
            'condensed' => $mode,
        ];
    }

    /**
     * Lista empresas com filtros aplicados.
     */
    public function listCompanies(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->companyRepository->listWithFilters($filters, $perPage);
    }

    /**
     * Busca avançada para API (autocomplete / filtros externos).
     */
    public function searchApi(array $filters): Collection
    {
        return $this->companyRepository->searchForApi($filters);
    }

    /**
     * Alterna um atributo booleano da empresa e retorna o novo valor.
     * Regra de negócio: inversão do estado atual + persistência.
     */
    public function toggleAttribute(Company $company, string $attribute): bool
    {
        if (in_array($attribute, ['is_active', 'financial_irregular'], true)) {
            throw new DomainException('A situação do cliente é controlada pelo financeiro.');
        }

        $company->$attribute = ! $company->$attribute;
        $this->companyRepository->save($company);

        return $company->$attribute;
    }
}
