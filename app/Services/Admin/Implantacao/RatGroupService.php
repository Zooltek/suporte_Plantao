<?php

declare(strict_types=1);

namespace App\Services\Admin\Implantacao;

use App\Contracts\Repositories\RatGroupRepositoryInterface;
use App\Models\Schedule\ElementGroup;
use Illuminate\Support\Collection;

class RatGroupService
{
    public function __construct(
        private readonly RatGroupRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, ElementGroup>
     */
    public function getAllWithItemCount(): Collection
    {
        return $this->repository->getAllWithItemCount();
    }

    public function createGroup(array $data): ElementGroup
    {
        return $this->repository->create([
            'name' => trim($data['name']),
        ]);
    }

    public function updateGroup(ElementGroup $group, array $data): ElementGroup
    {
        return $this->repository->update($group, [
            'name' => trim($data['name']),
        ]);
    }

    public function deleteGroup(ElementGroup $group): void
    {
        if ($this->repository->hasElementTypes($group)) {
            throw new \DomainException(
                "O grupo \"{$group->name}\" possui itens vinculados e não pode ser excluído. Remova ou mova os itens primeiro."
            );
        }

        $this->repository->delete($group);
    }
}
