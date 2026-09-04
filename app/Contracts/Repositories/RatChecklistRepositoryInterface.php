<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

interface RatChecklistRepositoryInterface
{
    /**
     * Retorna todos os Modules com seus ElementTypes e grupos (eager loading).
     *
     * @return Collection<int, Module>
     */
    public function getModulesWithItems(): Collection;

    /**
     * Retorna todos os ElementGroups ordenados por nome.
     *
     * @return Collection<int, ElementGroup>
     */
    public function getAllGroups(): Collection;

    /**
     * Retorna todos os Modules ordenados por projeto e nome.
     *
     * @return Collection<int, Module>
     */
    public function getAllModules(): Collection;

    /**
     * Busca um ElementGroup pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function findGroup(int $id): ElementGroup;

    /**
     * Busca ou cria um ElementGroup pelo nome.
     */
    public function firstOrCreateGroup(string $name): ElementGroup;

    /**
     * Persiste um novo ElementType (item de checklist RAT) no banco.
     *
     * @param  array<string, mixed>  $data
     */
    public function createItem(array $data): ElementType;

    /**
     * Atualiza um item de checklist e retorna a instância atualizada.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(ElementType $item, array $data): ElementType;

    /**
     * Remove um item de checklist do banco.
     */
    public function deleteItem(ElementType $item): void;

    /**
     * Verifica se o item possui registros de RAT vinculados.
     */
    public function itemHasElements(ElementType $item): bool;
}
