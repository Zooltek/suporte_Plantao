<?php

declare(strict_types=1);

namespace App\Services\Admin\Implantacao;

use App\Contracts\Repositories\RatModuleRepositoryInterface;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

class RatModuleService
{
    public function __construct(
        private readonly RatModuleRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Module>
     */
    public function getAllWithCounts(): Collection
    {
        return $this->repository->getAllWithCounts();
    }

    /**
     * @return array<string>
     */
    public function getDistinctProjects(): array
    {
        return $this->repository->getDistinctProjects();
    }

    public function createModule(array $data): Module
    {
        return $this->repository->create([
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'project'     => trim($data['project'] ?? ''),
        ]);
    }

    public function updateModule(Module $module, array $data): Module
    {
        return $this->repository->update($module, [
            'name'        => trim($data['name']),
            'description' => trim($data['description'] ?? ''),
            'project'     => trim($data['project'] ?? ''),
        ]);
    }

    public function deleteModule(Module $module): void
    {
        if ($this->repository->hasElementTypes($module)) {
            throw new \DomainException(
                "O módulo \"{$module->name}\" possui itens de checklist vinculados e não pode ser excluído. Remova os itens primeiro."
            );
        }

        $this->repository->delete($module);
    }
}
