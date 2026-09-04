<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface HomeRepositoryInterface
{
    /**
     * Retorna categorias raiz visíveis para a home.
     */
    public function getRootCategories(): Collection;

    /**
     * Retorna todas as soluções ativas.
     */
    public function getActiveSolutions(): Collection;

    /**
     * Retorna as N soluções mais recentes/em-destaque ativas.
     */
    public function getLatestSolutions(int $limit = 4): Collection;
}
