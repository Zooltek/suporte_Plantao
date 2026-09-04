<?php

namespace App\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ImplantacaoRepositoryInterface
{
    /**
     * Retorna os IDs de clientes que têm records de implantação ativos (status = 1).
     *
     * @return array<int>
     */
    public function getImplementationClientIds(): array;

    /**
     * Conta agendamentos ativos (scope active) excluindo status 'fin' e 'can'
     * que possuam ao menos um record ativo.
     */
    public function countActiveSchedules(): int;

    /**
     * Conta tickets abertos (status não-terminal) para os clientes informados.
     *
     * @param array<int> $clientIds
     */
    public function getOpenTicketsCount(array $clientIds): int;

    /**
     * Retorna o total de minutos trabalhados (via TIMESTAMPDIFF MySQL)
     * para os clients informados, descontando intervalos.
     *
     * @param array<int> $clientIds
     */
    public function getTotalMinutes(array $clientIds): int;

    /**
     * Retorna os clientes em implantação com eager loading de state e software.
     * Cada item recebe os atributos computados:
     *   - active_records  (int)
     *   - total_minutes   (int)
     *
     * @param  array<int> $clientIds
     */
    public function getClientsWithDetails(array $clientIds): Collection;

    /**
     * Retorna os agendamentos ativos de implantação paginados,
     * com eager loading de customer, agent, module, records e
     * contagem de active_records_count.
     */
    public function getSchedulesPaginated(int $perPage): LengthAwarePaginator;
}
