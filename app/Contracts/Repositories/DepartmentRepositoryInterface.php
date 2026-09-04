<?php

namespace App\Contracts\Repositories;

use App\Models\Department;
use Illuminate\Support\Collection;

interface DepartmentRepositoryInterface
{
    public function findWithLabels(int $id): Department;

    public function getAllWithLabels(): Collection;

    public function create(array $data): Department;

    public function update(Department $department, array $data): Department;

    public function findDefault(): Department;

    public function deleteWithFallback(Department $department): bool;

    public function getDepartmentStats(): array;
}
