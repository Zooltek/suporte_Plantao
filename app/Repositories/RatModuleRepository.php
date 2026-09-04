<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\RatModuleRepositoryInterface;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

class RatModuleRepository implements RatModuleRepositoryInterface
{
    public function getAllWithCounts(): Collection
    {
        return Module::withCount('elementTypes')
            ->orderBy('project')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): Module
    {
        return Module::findOrFail($id);
    }

    public function create(array $data): Module
    {
        return Module::create($data);
    }

    public function update(Module $module, array $data): Module
    {
        $module->update($data);

        return $module->fresh();
    }

    public function delete(Module $module): void
    {
        $module->delete();
    }

    public function hasElementTypes(Module $module): bool
    {
        return $module->elementTypes()->exists();
    }

    public function getDistinctProjects(): array
    {
        return Module::whereNotNull('project')
            ->where('project', '!=', '')
            ->distinct()
            ->orderBy('project')
            ->pluck('project')
            ->all();
    }
}
