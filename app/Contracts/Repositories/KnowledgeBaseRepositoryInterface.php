<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Category;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Collection;

interface KnowledgeBaseRepositoryInterface
{
    /**
     * Retorna categorias raiz visíveis e ativas, ordenadas por sort_order.
     */
    public function getVisibleRootCategories(): Collection;

    /**
     * Retorna soluções ativas excluindo a fornecida, buscando por tags.
     */
    public function getRelatedSolutions(Solution $solution, int $limit = 5): Collection;
}
