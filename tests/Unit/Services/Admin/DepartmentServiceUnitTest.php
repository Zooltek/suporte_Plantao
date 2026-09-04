<?php

use App\Contracts\Repositories\DepartmentRepositoryInterface;
use App\Models\Department;
use App\Services\Admin\DepartmentService;
use Illuminate\Support\Collection;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ds_service(DepartmentRepositoryInterface $repo): DepartmentService
{
    return new DepartmentService($repo);
}

function ds_makeDept(array $attrs = []): Department
{
    $dept = new Department(array_merge(['name' => 'Test', 'description' => null], $attrs));
    $dept->id = $attrs['id'] ?? 1;

    return $dept;
}

// ─── getAllDepartments ────────────────────────────────────────────────────────

describe('DepartmentService — getAllDepartments', function () {

    it('delega ao repositório e retorna a coleção', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $collection = new Collection([ds_makeDept(), ds_makeDept(['name' => 'Outro'])]);

        $repo->shouldReceive('getAllWithLabels')
            ->once()
            ->andReturn($collection);

        $result = ds_service($repo)->getAllDepartments();

        expect($result)->toBe($collection);
    });

});

// ─── findDepartment ───────────────────────────────────────────────────────────

describe('DepartmentService — findDepartment', function () {

    it('delega ao repositório com o id correto', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $dept = ds_makeDept(['id' => 42]);

        $repo->shouldReceive('findWithLabels')
            ->once()
            ->with(42)
            ->andReturn($dept);

        $result = ds_service($repo)->findDepartment(42);

        expect($result)->toBe($dept);
    });

});

// ─── createDepartment ────────────────────────────────────────────────────────

describe('DepartmentService — createDepartment', function () {

    it('delega ao repositório com o array de dados', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $data = ['name' => 'Novo', 'description' => 'Desc'];
        $dept = ds_makeDept($data);

        $repo->shouldReceive('create')
            ->once()
            ->with($data)
            ->andReturn($dept);

        $result = ds_service($repo)->createDepartment($data);

        expect($result)->toBe($dept);
    });

});

// ─── updateDepartment ────────────────────────────────────────────────────────

describe('DepartmentService — updateDepartment', function () {

    it('delega ao repositório passando department e dados', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $dept    = ds_makeDept(['name' => 'Antigo']);
        $data    = ['name' => 'Novo'];
        $updated = ds_makeDept(['name' => 'Novo']);

        $repo->shouldReceive('update')
            ->once()
            ->with($dept, $data)
            ->andReturn($updated);

        $result = ds_service($repo)->updateDepartment($dept, $data);

        expect($result)->toBe($updated);
    });

});

// ─── deleteDepartment ────────────────────────────────────────────────────────

describe('DepartmentService — deleteDepartment', function () {

    it('delega ao repositório e retorna o resultado booleano', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $dept = ds_makeDept();

        $repo->shouldReceive('deleteWithFallback')
            ->once()
            ->with($dept)
            ->andReturn(true);

        $result = ds_service($repo)->deleteDepartment($dept);

        expect($result)->toBeTrue();
    });

});

// ─── getDepartmentStats ───────────────────────────────────────────────────────

describe('DepartmentService — getDepartmentStats', function () {

    it('delega ao repositório e retorna o array de stats', function () {
        $repo = Mockery::mock(DepartmentRepositoryInterface::class);

        $stats = ['total' => 5, 'withModules' => 3, 'empty' => 2];

        $repo->shouldReceive('getDepartmentStats')
            ->once()
            ->andReturn($stats);

        $result = ds_service($repo)->getDepartmentStats();

        expect($result)->toBe($stats);
    });

});
