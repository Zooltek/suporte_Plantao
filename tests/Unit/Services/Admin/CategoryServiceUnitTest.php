<?php

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use App\Models\CategoryDescription;
use App\Services\Admin\CategoryService;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cs_makeCategory(array $attrs = []): Category
{
    $category = new Category(array_merge([
        'parent_id' => 0,
        'priority' => 'low',
    ], $attrs));
    $category->category_id = $attrs['category_id'] ?? 1;

    return $category;
}

function cs_makeDescription(array $attrs = []): CategoryDescription
{
    return new CategoryDescription(array_merge([
        'name' => 'Nome Padrão',
        'description' => 'Desc',
        'permalink' => 'nome-padrao',
    ], $attrs));
}

function cs_service(CategoryRepositoryInterface $repo): CategoryService
{
    return new CategoryService($repo);
}

// ─── create — root ────────────────────────────────────────────────────────────

describe('CategoryService — create (root)', function () {

    it('chama createCategory com parent_id=0 quando parent_id é null', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $expectedCategory = cs_makeCategory(['parent_id' => 0]);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(function (array $catData, array $descData) {
                return $catData['parent_id'] === 0
                    && isset($descData['name'])
                    && isset($descData['permalink']);
            })
            ->andReturn($expectedCategory);

        $result = cs_service($repo)->create([
            'name' => 'Categoria Raiz',
            'parent_id' => null,
        ]);

        expect($result)->toBe($expectedCategory);
    });

    it('chama createCategory com parent_id=0 quando parent_id é "0"', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $expectedCategory = cs_makeCategory(['parent_id' => 0]);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn ($catData) => $catData['parent_id'] === 0)
            ->andReturn($expectedCategory);

        cs_service($repo)->create(['name' => 'Cat', 'parent_id' => '0']);
    });

    it('gera permalink via slug do name quando permalink não fornecido', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(function (array $catData, array $descData) {
                return $descData['permalink'] === 'minha-categoria';
            })
            ->andReturn(cs_makeCategory());

        cs_service($repo)->create(['name' => 'Minha Categoria', 'parent_id' => null]);
    });

    it('usa permalink fornecido quando não vazio', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(function (array $catData, array $descData) {
                return $descData['permalink'] === 'meu-slug-customizado';
            })
            ->andReturn(cs_makeCategory());

        cs_service($repo)->create([
            'name' => 'Minha Categoria',
            'parent_id' => null,
            'permalink' => 'meu-slug-customizado',
        ]);
    });

});

// ─── create — parent_id inválido ─────────────────────────────────────────────

describe('CategoryService — create com parent_id inválido', function () {

    it('lança InvalidArgumentException quando parent_id não existe no banco', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('findCategory')
            ->once()
            ->with(99)
            ->andReturn(null);

        expect(fn () => cs_service($repo)->create([
            'name' => 'Sub',
            'parent_id' => 99,
        ]))->toThrow(\InvalidArgumentException::class, 'Categoria inválida.');
    });

});

// ─── createSubcategory — ensureParentCanReceiveChildren ──────────────────────

describe('CategoryService — createSubcategory', function () {

    it('lança InvalidArgumentException quando parent é uma subcategoria (tem parent_id)', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        // parent já é filho de outro (parent_id = 5)
        $grandchild = cs_makeCategory(['parent_id' => 5, 'category_id' => 10]);
        $grandchild->parent_id = 5;

        $repo->shouldReceive('findCategory')
            ->once()
            ->with(10)
            ->andReturn($grandchild);

        expect(fn () => cs_service($repo)->createSubcategory([
            'name' => 'Sub de Sub',
            'parent_id' => 10,
        ]))->toThrow(\InvalidArgumentException::class, 'Subcategorias não podem ter outras subcategorias.');
    });

    it('lança InvalidArgumentException quando parent_id não é fornecido', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        expect(fn () => cs_service($repo)->createSubcategory([
            'name' => 'Sub',
            'parent_id' => null,
        ]))->toThrow(\InvalidArgumentException::class, 'Selecione uma categoria válida para criar a subcategoria.');
    });

});

// ─── update — categoria com filhos tentando virar subcategoria ────────────────

describe('CategoryService — update', function () {

    it('lança InvalidArgumentException ao tentar tornar subcategoria uma categoria que tem filhos', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = cs_makeCategory(['parent_id' => 0, 'category_id' => 1]);
        $category->description = cs_makeDescription();

        $repo->shouldReceive('categoryHasChildren')
            ->once()
            ->with($category)
            ->andReturn(true);

        expect(fn () => cs_service($repo)->update($category, [
            'parent_id' => 2,
        ]))->toThrow(\InvalidArgumentException::class, 'Não é possível transformar em subcategoria uma categoria que já possui subcategorias.');
    });

    it('delega a atualização ao repositório quando os dados são válidos', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = cs_makeCategory(['parent_id' => 0, 'category_id' => 1, 'priority' => 'low']);
        $description = cs_makeDescription(['name' => 'Original']);
        $category->description = $description;

        $expectedUpdated = cs_makeCategory(['priority' => 'high']);

        $repo->shouldReceive('updateCategory')
            ->once()
            ->withArgs(function (Category $cat, array $catPayload, ?array $descPayload) {
                return $catPayload['priority'] === 'high'
                    && $descPayload !== null
                    && $descPayload['name'] === 'Novo Nome';
            })
            ->andReturn($expectedUpdated);

        $result = cs_service($repo)->update($category, [
            'priority' => 'high',
            'name' => 'Novo Nome',
        ]);

        expect($result)->toBe($expectedUpdated);
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('CategoryService — delete', function () {

    it('lança InvalidArgumentException quando a categoria tem filhos', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = cs_makeCategory();

        $repo->shouldReceive('categoryHasChildren')
            ->once()
            ->with($category)
            ->andReturn(true);

        expect(fn () => cs_service($repo)->delete($category))
            ->toThrow(\InvalidArgumentException::class, 'Não é possível excluir uma categoria que possui subcategorias.');
    });

    it('lança InvalidArgumentException quando a categoria tem soluções vinculadas', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = cs_makeCategory();

        $repo->shouldReceive('categoryHasChildren')
            ->once()
            ->with($category)
            ->andReturn(false);

        $repo->shouldReceive('categoryHasSolutions')
            ->once()
            ->with($category)
            ->andReturn(true);

        expect(fn () => cs_service($repo)->delete($category))
            ->toThrow(\InvalidArgumentException::class, 'Não é possível excluir uma categoria que possui soluções vinculadas.');
    });

    it('chama deleteCategory no repo quando não há restrições', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $category = cs_makeCategory();

        $repo->shouldReceive('categoryHasChildren')
            ->once()
            ->with($category)
            ->andReturn(false);

        $repo->shouldReceive('categoryHasSolutions')
            ->once()
            ->with($category)
            ->andReturn(false);

        $repo->shouldReceive('deleteCategory')
            ->once()
            ->with($category);

        cs_service($repo)->delete($category);
    });

});

// ─── normalizeParentId (via create) ──────────────────────────────────────────

describe('CategoryService — normalizeParentId via create', function () {

    it('trata 0 como null (root)', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn ($catData) => $catData['parent_id'] === 0)
            ->andReturn(cs_makeCategory());

        cs_service($repo)->create(['name' => 'Cat', 'parent_id' => 0]);
    });

    it('trata string vazia como null (root)', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn ($catData) => $catData['parent_id'] === 0)
            ->andReturn(cs_makeCategory());

        cs_service($repo)->create(['name' => 'Cat', 'parent_id' => '']);
    });

    it('trata string "0" como null (root)', function () {
        $repo = Mockery::mock(CategoryRepositoryInterface::class);

        $repo->shouldReceive('createCategory')
            ->once()
            ->withArgs(fn ($catData) => $catData['parent_id'] === 0)
            ->andReturn(cs_makeCategory());

        cs_service($repo)->create(['name' => 'Cat', 'parent_id' => '0']);
    });

});
