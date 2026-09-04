<?php

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Services\Admin\CategoryService;

function csd_makeCategory(array $attrs = []): Category
{
    $category = new Category(array_merge([
        'parent_id' => 0,
        'priority' => 'low',
    ], $attrs));
    $category->category_id = $attrs['category_id'] ?? 1;

    if (array_key_exists('department_id', $attrs)) {
        $category->department_id = $attrs['department_id'];
    }

    return $category;
}

function csd_makeDescription(): CategoryDescription
{
    return new CategoryDescription([
        'name' => 'Nome Padrão',
        'description' => 'Desc',
        'permalink' => 'nome-padrao',
    ]);
}

function csd_service(CategoryRepositoryInterface $repo): CategoryService
{
    return new CategoryService($repo);
}

describe('CategoryService — department_id propagation (root)', function () {

    it('persiste department_id quando fornecido na categoria pai', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn (array $catData) => $catData['department_id'] === 3)
            ->andReturn(csd_makeCategory(['department_id' => 3]));

        csd_service($repo)->createRootCategory([
            'name' => 'Comercial',
            'priority' => 'high',
            'department_id' => 3,
        ]);
    });

    it('persiste department_id como null quando não fornecido', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn (array $catData) => $catData['department_id'] === null)
            ->andReturn(csd_makeCategory());

        csd_service($repo)->createRootCategory(['name' => 'Cat', 'priority' => 'low']);
    });

    it('persiste department_id como null quando recebe string vazia ou "0"', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->twice()
            ->withArgs(fn (array $catData) => $catData['department_id'] === null)
            ->andReturn(csd_makeCategory());

        csd_service($repo)->createRootCategory(['name' => 'A', 'priority' => 'low', 'department_id' => '']);
        csd_service($repo)->createRootCategory(['name' => 'B', 'priority' => 'low', 'department_id' => '0']);
    });

});

describe('CategoryService — department_id propagation (subcategory)', function () {

    it('persiste department_id quando explicitamente fornecido na subcategoria', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $parent = csd_makeCategory(['parent_id' => 0, 'category_id' => 1, 'department_id' => 2]);

        $repo->shouldReceive('findCategory')
            ->once()
            ->with(1)
            ->andReturn($parent);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn (array $catData) => $catData['department_id'] === 5)
            ->andReturn(csd_makeCategory());

        csd_service($repo)->createSubcategory([
            'name' => 'Sub',
            'parent_id' => 1,
            'priority' => 'low',
            'department_id' => 5,
        ]);
    });

    it('herda department_id do parent quando subcategoria não informa', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $parent = csd_makeCategory(['parent_id' => 0, 'category_id' => 1, 'department_id' => 7]);

        $repo->shouldReceive('findCategory')
            ->once()
            ->with(1)
            ->andReturn($parent);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn (array $catData) => $catData['department_id'] === 7)
            ->andReturn(csd_makeCategory());

        csd_service($repo)->createSubcategory([
            'name' => 'Sub',
            'parent_id' => 1,
            'priority' => 'low',
        ]);
    });

    it('mantém null quando parent não tem departamento e subcategoria também não informa', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $parent = csd_makeCategory(['parent_id' => 0, 'category_id' => 1]);

        $repo->shouldReceive('findCategory')
            ->once()
            ->with(1)
            ->andReturn($parent);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn (array $catData) => $catData['department_id'] === null)
            ->andReturn(csd_makeCategory());

        csd_service($repo)->createSubcategory([
            'name' => 'Sub',
            'parent_id' => 1,
            'priority' => 'low',
        ]);
    });

});

describe('CategoryService — department_id propagation (update)', function () {

    it('inclui department_id no payload de update quando fornecido', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = csd_makeCategory(['parent_id' => 0, 'category_id' => 1, 'priority' => 'low']);
        $category->description = csd_makeDescription();

        $repo->shouldReceive('updateCategory')
            ->once()
            ->withArgs(function (Category $cat, array $catPayload) {
                return array_key_exists('department_id', $catPayload)
                    && $catPayload['department_id'] === 3;
            })
            ->andReturn($category);

        csd_service($repo)->update($category, [
            'priority' => 'low',
            'department_id' => 3,
        ]);
    });

    it('omite department_id do payload quando não fornecido', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = csd_makeCategory(['parent_id' => 0, 'category_id' => 1, 'priority' => 'low']);
        $category->description = csd_makeDescription();

        $repo->shouldReceive('updateCategory')
            ->once()
            ->withArgs(function (Category $cat, array $catPayload) {
                return ! array_key_exists('department_id', $catPayload);
            })
            ->andReturn($category);

        csd_service($repo)->update($category, ['priority' => 'high']);
    });

    it('normaliza string vazia como null no update', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = csd_makeCategory(['parent_id' => 0, 'category_id' => 1, 'priority' => 'low']);
        $category->description = csd_makeDescription();

        $repo->shouldReceive('updateCategory')
            ->once()
            ->withArgs(function (Category $cat, array $catPayload) {
                return array_key_exists('department_id', $catPayload)
                    && $catPayload['department_id'] === null;
            })
            ->andReturn($category);

        csd_service($repo)->update($category, [
            'priority' => 'low',
            'department_id' => '',
        ]);
    });

});
