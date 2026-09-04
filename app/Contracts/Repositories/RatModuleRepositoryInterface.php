<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

interface RatModuleRepositoryInterface
{
    /**
     * @return Collection<int, Module>
     */
    public function getAllWithCounts(): Collection;

    public function find(int $id): Module;

    public function create(array $data): Module;

    public function update(Module $module, array $data): Module;

    public function delete(Module $module): void;

    public function hasElementTypes(Module $module): bool;

    /**
     * @return array<string>
     */
    public function getDistinctProjects(): array;
}
