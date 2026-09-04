<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\RatGroupRepositoryInterface;
use App\Models\Schedule\ElementGroup;
use Illuminate\Support\Collection;

class RatGroupRepository implements RatGroupRepositoryInterface
{
    public function getAllWithItemCount(): Collection
    {
        return ElementGroup::withCount('elementTypes')
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ElementGroup
    {
        return ElementGroup::findOrFail($id);
    }

    public function create(array $data): ElementGroup
    {
        return ElementGroup::create($data);
    }

    public function update(ElementGroup $group, array $data): ElementGroup
    {
        $group->update($data);

        return $group->fresh();
    }

    public function delete(ElementGroup $group): void
    {
        $group->delete();
    }

    public function hasElementTypes(ElementGroup $group): bool
    {
        return $group->elementTypes()->exists();
    }
}
