<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;
use Illuminate\Support\Collection;

class DepartmentService
{
    public function __construct(
        private readonly DepartmentRepositoryInterface $departmentRepository
    ) {}

    public function findDepartment(int $id): Department
    {
        return $this->departmentRepository->findWithLabels($id);
    }

    public function getAllDepartments(): Collection
    {
        return $this->departmentRepository->getAllWithLabels();
    }

    public function createDepartment(array $data): Department
    {
        return $this->departmentRepository->create($data);
    }

    public function updateDepartment(Department $department, array $data): Department
    {
        return $this->departmentRepository->update($department, $data);
    }

    public function findDefaultDepartment(): Department
    {
        return $this->departmentRepository->findDefault();
    }

    public function deleteDepartment(Department $department): bool
    {
        if ($department->is_default) {
            throw new \InvalidArgumentException('O departamento padrão não pode ser excluído.');
        }

        return $this->departmentRepository->deleteWithFallback($department);
    }

    public function getDepartmentStats(): array
    {
        return $this->departmentRepository->getDepartmentStats();
    }
}
