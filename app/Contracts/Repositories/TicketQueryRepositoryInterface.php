<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface TicketQueryRepositoryInterface
{
    /**
     * Executa a query paginada de tickets com eager loading, filtros e ordenação.
     *
     * @param  array{q?: string, status?: int, category?: int, company?: int, agent?: int|string, origin?: int, date_from?: string|null, date_to?: string|null}  $filters
     * @param  int|null  $agentScope  Quando não-null, inclui tickets do agente e fila do setor.
     * @param  int  $order  1=última atualização, 2=mais recentes (pendentes primeiro, created_at desc), default=prioridade+tempo
     */
    public function paginateTickets(
        array $filters,
        ?int $agentScope,
        ?int $departmentScope,
        bool $includeDepartmentQueue,
        int $order,
        int $perPage,
    ): LengthAwarePaginator;

    /**
     * Retorna todos os status ordenados por nome (para o seletor de filtro).
     */
    public function getAllStatuses(): Collection;

    /**
     * Retorna opções de categoria prontas para o filtro da listagem.
     */
    public function getCategoryFilterOptions(): Collection;

    /**
     * Retorna todas as empresas ordenadas por trade_name.
     */
    public function getAllCompanies(): Collection;

    /**
     * Retorna agentes ativos (ticketit_agent = 1) ordenados por nome.
     */
    public function getActiveAgents(): Collection;

    /**
     * Retorna canais de origem para filtro da listagem.
     */
    public function getAllOrigins(): Collection;
}
