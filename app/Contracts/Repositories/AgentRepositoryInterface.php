<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface AgentRepositoryInterface
{
    /**
     * Lista agentes com contagem de tickets recentes e eager loading do departamento.
     *
     * @param  bool        $showAll  Se false, restringe a ticketit_agent = 1.
     * @param  string|null $query    Termo de busca livre.
     */
    public function listAgents(bool $showAll, ?string $query = null): Collection;

    /**
     * Retorna agentes do departamento 1, com média e contagem de avaliações.
     */
    public function getAgentRatings(): Collection;

    /**
     * Retorna agentes aniversariantes do dia, excluindo o ID informado.
     */
    public function getBirthdays(int $excludeUserId): Collection;
}
