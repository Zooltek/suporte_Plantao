<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use Illuminate\Support\Collection;

class AtendimentoService
{
    public function __construct(
        private readonly AtendimentoRepositoryInterface $repository,
    ) {}

    /**
     * Busca categorias raiz (parent_id = 0) com cache.
     */
    public function getRootCategories(): Collection
    {
        return $this->repository->getRootCategories();
    }

    /**
     * Busca categorias filhas com cache por ID pai.
     */
    public function getChildren(int $parentId): Collection
    {
        return $this->repository->getChildrenByParent($parentId);
    }

    /**
     * Busca soluções relacionadas ativas baseado em texto.
     */
    public function searchSolutions(string $query): Collection
    {
        return $this->repository->searchSolutions($query);
    }
}
