<?php

namespace App\Repositories;

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    public function findWithLabels(int $id): Department
    {
        return Department::with(['labels' => function ($query) {
            $query->whereNull('parent_id')->where('is_active', true)->orderBy('name');
        }])->findOrFail($id);
    }

    public function getAllWithLabels(): Collection
    {
        return Department::with(['labels' => function ($query) {
            $query->whereNull('parent_id')->where('is_active', true)->orderBy('name');
        }])->orderBy('name')->get();
    }

    public function create(array $data): Department
    {
        return Department::create([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
        ]);
    }

    public function update(Department $department, array $data): Department
    {
        $department->update([
            'name'        => $data['name'] ?? $department->name,
            'description' => $data['description'] ?? $department->description,
        ]);

        return $department->fresh();
    }

    public function findDefault(): Department
    {
        $default = Department::where('is_default', true)->first();

        if (! $default) {
            $default = Department::create([
                'name'        => 'Geral',
                'description' => 'Departamento padrão',
                'is_default'  => true,
            ]);
        }

        return $default;
    }

    public function deleteWithFallback(Department $department): bool
    {
        return DB::transaction(function () use ($department): bool {
            $fallback = $this->findDefault();

            DB::table('users')
                ->where('department_id', $department->id)
                ->update(['department_id' => $fallback->id]);

            DB::table('ticketit')
                ->where('department_id', $department->id)
                ->update(['department_id' => $fallback->id]);

            $department->labels()->update(['department_id' => $fallback->id]);

            return (bool) $department->delete();
        });
    }

    public function getDepartmentStats(): array
    {
        $linkedModulesFilter = function ($query) {
            $query->whereNull('parent_id')->where('is_active', true);
        };

        return [
            'total'       => Department::count(),
            'withModules' => Department::whereHas('labels', $linkedModulesFilter)->count(),
            'empty'       => Department::whereDoesntHave('labels', $linkedModulesFilter)->count(),
        ];
    }
}
