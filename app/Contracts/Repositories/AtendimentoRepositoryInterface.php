<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Support\Collection;

interface AtendimentoRepositoryInterface
{
    /**
     * Retorna categorias raiz (parent_id = 0) com cache.
     */
    public function getRootCategories(): Collection;

    /**
     * Retorna categorias filhas de um pai com cache.
     */
    public function getChildrenByParent(int $parentId): Collection;

    /**
     * Busca soluções ativas que contenham o texto no título ou conteúdo.
     */
    public function searchSolutions(string $query): Collection;
}
