<?php

namespace App\Contracts\Repositories;

use App\Models\Company;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    /**
     * Pagina os tickets de uma empresa aplicando os filtros suportados
     * (start, end, agent_id, category_id).
     */
    public function paginateCompanyTickets(
        Company $company,
        array $filters,
        int $perPage = 15,
    ): LengthAwarePaginator;

    /**
     * Retorna uma coleção limitada de tickets da empresa (modo AJAX/condensed).
     */
    public function limitCompanyTickets(
        Company $company,
        array $filters,
        int $take,
    ): Collection;

    /**
     * Retorna os IDs dos status terminais (com cache — raramente mudam).
     *
     * @return int[]
     */
    public function getTerminalStatusIds(): array;

    /**
     * Retorna a fila de pendências da empresa (pendente e sem agente).
     *
     * @param  int[]  $terminalIds  Mantido por compatibilidade de contrato.
     */
    public function getPendingTickets(Company $company, array $terminalIds): Collection;

    /**
     * Retorna os últimos tickets finalizados da empresa.
     *
     * @param  int[]  $terminalIds
     */
    public function getFinalizedTickets(
        Company $company,
        array $terminalIds,
        int $limit = 5,
    ): Collection;

    /**
     * Retorna os agendamentos da empresa ordenados por data decrescente.
     */
    public function getCompanySchedules(Company $company): Collection;

    /**
     * Lista empresas com filtros opcionais paginados.
     */
    public function listWithFilters(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Busca avançada para API (autocomplete / filtros externos).
     * Retorna array de dados simplificados para renderização via Alpine.js.
     */
    public function searchForApi(array $filters): Collection;

    /**
     * Retorna o último contexto técnico válido (version/release) por empresa.
     *
     * @param  int[]  $companyIds
     * @return array<int, array{version: string, release: string, created_at: mixed}>
     */
    public function getLatestTicketTechnicalContexts(array $companyIds): array;

    /**
     * Persiste o estado atual da empresa no banco.
     */
    public function save(Company $company): void;
}
