<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Category;
use App\Models\CategoryDescription;
use Illuminate\Support\Collection;

interface HelpdeskCategoryRepositoryInterface
{
    /**
     * Retorna categorias raiz de um setor específico.
     */
    public function getRootByTicketCategory(int $ticketCategoryId): Collection;

    /**
     * Persiste (cria ou atualiza) categoria e sua descrição em transação.
     * Invalida o cache hierárquico ao final.
     */
    public function storeOrUpdate(array $data, ?int $id = null): Category;

    /**
     * Retorna categorias filhas com description carregada, por parent_id.
     */
    public function getChildrenWithDescription(int $parentId): Collection;

    /**
     * Busca uma categoria pelo ID primário; lança ModelNotFoundException se ausente.
     */
    public function findOrFail(int $id): Category;
}
