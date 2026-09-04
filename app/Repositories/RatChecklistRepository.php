<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\RatChecklistRepositoryInterface;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

class RatChecklistRepository implements RatChecklistRepositoryInterface
{
    public function getModulesWithItems(): Collection
    {
        return Module::orderBy('project')->orderBy('name')
            ->with(['elementTypes' => fn ($q) => $q->with('group')->orderBy('group_id')->orderBy('label')])
            ->get();
    }

    public function getAllGroups(): Collection
    {
        return ElementGroup::orderBy('name')->get();
    }

    public function getAllModules(): Collection
    {
        return Module::orderBy('project')->orderBy('name')->get();
    }

    public function findGroup(int $id): ElementGroup
    {
        return ElementGroup::findOrFail($id);
    }

    public function firstOrCreateGroup(string $name): ElementGroup
    {
        return ElementGroup::firstOrCreate(['name' => trim($name)]);
    }

    public function createItem(array $data): ElementType
    {
        return ElementType::create($data);
    }

    public function updateItem(ElementType $item, array $data): ElementType
    {
        $item->update($data);

        return $item->fresh();
    }

    public function deleteItem(ElementType $item): void
    {
        $item->delete();
    }

    public function itemHasElements(ElementType $item): bool
    {
        return $item->elements()->exists();
    }
}
