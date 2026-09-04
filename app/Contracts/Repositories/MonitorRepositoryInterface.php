<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

interface MonitorRepositoryInterface
{
    /**
     * Retorna os agentes ativos (excluindo departamentos 3 e 4) com subqueries
     * de contagem para o painel de monitoramento.
     */
    public function getAgentsWithStats(Carbon $start, Carbon $end, Carbon $now): Collection;

    /**
     * Retorna apenas os horários (created_at) dos tickets criados no período,
     * para cálculo do gráfico de distribuição por intervalo de 30 min.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    public function getTicketTimesInPeriod(Carbon $start, Carbon $end): \Illuminate\Support\Collection;
}
