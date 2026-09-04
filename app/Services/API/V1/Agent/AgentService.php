<?php

namespace App\Services\API\V1\Agent;

use App\Contracts\Repositories\AgentRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class AgentService
{
    public function __construct(
        private readonly AgentRepositoryInterface $repository
    ) {}

    /**
     * Lista agentes com contagem de tickets e filtros.
     */
    public function listAgents(bool $showAll, ?string $query = null): Collection
    {
        return $this->repository->listAgents($showAll, $query);
    }

    /**
     * Calcula o ranking de atendimento baseada em UserRating.
     * Ordena de forma composta: rating desc, count desc.
     */
    public function getAgentRatings(): Collection
    {
        return $this->repository->getAgentRatings()
            ->map(fn ($agent) => [
                'rating' => number_format($agent->rating_avg ?? 0, 2, '.', ''),
                'count'  => $agent->rating_count,
                'agent'  => $agent,
            ])
            ->sortByDesc(fn ($data) => [$data['rating'], $data['count']])
            ->values();
    }

    /**
     * Busca aniversariantes do dia (excluindo o usuário logado).
     */
    public function getBirthdays(): Collection
    {
        return $this->repository->getBirthdays((int) Auth::id());
    }
}
