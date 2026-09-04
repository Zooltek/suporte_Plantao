<?php

declare(strict_types=1);

namespace App\Services\Admin\Implantacao;

use App\Contracts\Repositories\RatChecklistRepositoryInterface;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

/**
 * Gerencia os itens de checklist do RAT (ElementType) e seus grupos.
 * Itens são vinculados a um Módulo e agrupados por ElementGroup.
 */
class RatChecklistService
{
    public function __construct(
        private readonly RatChecklistRepositoryInterface $repository,
    ) {}

    /**
     * Retorna todos os módulos com seus grupos e itens para o index.
     *
     * @return Collection<int, Module>
     */
    public function getModulesWithItems(): Collection
    {
        return $this->repository->getModulesWithItems();
    }

    /**
     * Retorna todos os grupos disponíveis para o select do formulário.
     *
     * @return Collection<int, ElementGroup>
     */
    public function getAllGroups(): Collection
    {
        return $this->repository->getAllGroups();
    }

    /**
     * Retorna todos os módulos para o select do formulário.
     *
     * @return Collection<int, Module>
     */
    public function getAllModules(): Collection
    {
        return $this->repository->getAllModules();
    }

    /**
     * Cria um novo item de checklist.
     * Se o nome do grupo não existir, cria automaticamente.
     */
    public function createItem(array $data): ElementType
    {
        $group = $this->resolveGroup($data['group_id'] ?? null, $data['new_group_name'] ?? null);

        return $this->repository->createItem([
            'name'      => $data['name'],
            'label'     => $data['label'],
            'type'      => 'checkbox',
            'module_id' => $data['module_id'],
            'group_id'  => $group->id,
        ]);
    }

    /**
     * Atualiza um item de checklist existente.
     */
    public function updateItem(ElementType $item, array $data): ElementType
    {
        $group = $this->resolveGroup($data['group_id'] ?? null, $data['new_group_name'] ?? null);

        return $this->repository->updateItem($item, [
            'name'      => $data['name'],
            'label'     => $data['label'],
            'module_id' => $data['module_id'],
            'group_id'  => $group->id,
        ]);
    }

    /**
     * Remove um item de checklist.
     * Lança DomainException se o item possui registros vinculados.
     */
    public function deleteItem(ElementType $item): void
    {
        if ($this->repository->itemHasElements($item)) {
            throw new \DomainException(
                "O item \"{$item->label}\" possui registros de RAT vinculados e não pode ser excluído."
            );
        }

        $this->repository->deleteItem($item);
    }

    /**
     * Resolve o grupo: usa existente ou cria novo.
     */
    private function resolveGroup(?int $groupId, ?string $newGroupName): ElementGroup
    {
        if ($groupId) {
            return $this->repository->findGroup($groupId);
        }

        if ($newGroupName) {
            return $this->repository->firstOrCreateGroup($newGroupName);
        }

        throw new \InvalidArgumentException('Informe um grupo existente ou um nome para o novo grupo.');
    }
}
