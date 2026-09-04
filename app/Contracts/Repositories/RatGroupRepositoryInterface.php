<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Schedule\ElementGroup;
use Illuminate\Support\Collection;

interface RatGroupRepositoryInterface
{
    /**
     * @return Collection<int, ElementGroup>
     */
    public function getAllWithItemCount(): Collection;

    public function find(int $id): ElementGroup;

    public function create(array $data): ElementGroup;

    public function update(ElementGroup $group, array $data): ElementGroup;

    public function delete(ElementGroup $group): void;

    public function hasElementTypes(ElementGroup $group): bool;
}
