<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Solution;
use App\Repositories\CategoryRepository;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cat_root(array $attrs = []): Category
{
    $category = Category::factory()->create(array_merge(['parent_id' => 0], $attrs));
    CategoryDescription::factory()->create(['category_id' => $category->category_id]);

    return $category->fresh('description');
}

function cat_child(Category $parent, array $attrs = []): Category
{
    $category = Category::factory()->create(array_merge(
        ['parent_id' => $parent->category_id],
        $attrs
    ));
    CategoryDescription::factory()->create(['category_id' => $category->category_id]);

    return $category->fresh('description');
}

function cat_repo(): CategoryRepository
{
    return new CategoryRepository();
}

// ─── getAllCategories ─────────────────────────────────────────────────────────

describe('CategoryRepository — getAllCategories', function () {

    it('retorna todas as categorias com eager loading de description, parent e children', function () {
        $root  = cat_root();
        $child = cat_child($root);

        $result = cat_repo()->getAllCategories();

        expect($result->count())->toBeGreaterThanOrEqual(2);

        $foundRoot = $result->firstWhere('category_id', $root->category_id);
        expect($foundRoot)->not->toBeNull();
        expect($foundRoot->relationLoaded('description'))->toBeTrue();
        expect($foundRoot->relationLoaded('children'))->toBeTrue();
        expect($foundRoot->relationLoaded('parent'))->toBeTrue();
    });

    it('ordena por priority desc (alfabético) e depois por category_id asc', function () {
        // priority é string — DESC alfabético: urgent > low > high
        $low    = cat_root(['priority' => 'low']);
        $urgent = cat_root(['priority' => 'urgent']);
        $high   = cat_root(['priority' => 'high']);

        $result = cat_repo()->getAllCategories();

        // Filtra apenas as categorias criadas neste teste
        $testIds        = [$low->category_id, $urgent->category_id, $high->category_id];
        $testCategories = $result->filter(fn ($c) => in_array($c->category_id, $testIds))->values();

        expect($testCategories->count())->toBe(3);

        $priorityOrder = $testCategories->pluck('priority')->toArray();

        // Ordem esperada (DESC alfabético): urgent > low > high
        $urgentIdx = array_search('urgent', $priorityOrder);
        $lowIdx    = array_search('low', $priorityOrder);
        $highIdx   = array_search('high', $priorityOrder);

        expect($urgentIdx)->toBeLessThan($lowIdx);
        expect($lowIdx)->toBeLessThan($highIdx);
    });

});

// ─── getParentCategories ──────────────────────────────────────────────────────

describe('CategoryRepository — getParentCategories', function () {

    it('retorna apenas categorias raiz (parent_id IS NULL ou 0)', function () {
        $root  = cat_root(['parent_id' => 0]);
        $child = cat_child($root);

        $result = cat_repo()->getParentCategories();

        $ids = $result->pluck('category_id')->toArray();
        expect($ids)->toContain($root->category_id);
        expect($ids)->not->toContain($child->category_id);
    });

    it('carrega a relação description', function () {
        $root = cat_root();

        $result = cat_repo()->getParentCategories();

        $found = $result->firstWhere('category_id', $root->category_id);
        expect($found->relationLoaded('description'))->toBeTrue();
    });

});

// ─── findCategory ─────────────────────────────────────────────────────────────

describe('CategoryRepository — findCategory', function () {

    it('retorna a categoria pelo id', function () {
        $root = cat_root();

        $found = cat_repo()->findCategory($root->category_id);

        expect($found)->not->toBeNull();
        expect($found->category_id)->toBe($root->category_id);
    });

    it('retorna null quando a categoria não existe', function () {
        $found = cat_repo()->findCategory(999999);

        expect($found)->toBeNull();
    });

});

// ─── createCategory ───────────────────────────────────────────────────────────

describe('CategoryRepository — createCategory', function () {

    it('persiste Category e CategoryDescription no banco', function () {
        $categoryData = [
            'parent_id'          => 0,
            'sort_order'         => 0,
            'status'             => 1,
            'visible'            => 1,
            'ticket_category_id' => 1,
            'profile'            => 0,
            'header'             => 1,
            'priority'           => 'low',
        ];

        $descriptionData = [
            'name'        => 'Categoria Teste',
            'description' => 'Descrição',
            'permalink'   => 'categoria-teste',
        ];

        $category = cat_repo()->createCategory($categoryData, $descriptionData);

        expect($category)->toBeInstanceOf(Category::class);
        expect($category->exists)->toBeTrue();

        $this->assertDatabaseHas('solutions_category', ['category_id' => $category->category_id]);
        $this->assertDatabaseHas('solutions_category_description', [
            'category_id' => $category->category_id,
            'name'        => 'Categoria Teste',
            'permalink'   => 'categoria-teste',
        ]);
    });

    it('retorna a categoria com description e parent carregados', function () {
        $categoryData    = ['parent_id' => 0, 'sort_order' => 0, 'status' => 1, 'visible' => 1, 'ticket_category_id' => 1, 'profile' => 0, 'header' => 1, 'priority' => 'high'];
        $descriptionData = ['name' => 'Nova Cat', 'description' => null, 'permalink' => 'nova-cat'];

        $category = cat_repo()->createCategory($categoryData, $descriptionData);

        expect($category->relationLoaded('description'))->toBeTrue();
        expect($category->description->name)->toBe('Nova Cat');
    });

});

// ─── updateCategory ───────────────────────────────────────────────────────────

describe('CategoryRepository — updateCategory', function () {

    it('persiste alterações em category e description', function () {
        $root = cat_root(['priority' => 'low']);

        $updated = cat_repo()->updateCategory(
            $root,
            ['priority' => 'high'],
            ['name' => 'Nome Atualizado', 'description' => 'Desc', 'permalink' => 'nome-atualizado']
        );

        expect($updated->priority)->toBe('high');
        expect($updated->description->name)->toBe('Nome Atualizado');

        $this->assertDatabaseHas('solutions_category', ['category_id' => $root->category_id, 'priority' => 'high']);
        $this->assertDatabaseHas('solutions_category_description', ['category_id' => $root->category_id, 'name' => 'Nome Atualizado']);
    });

    it('não atualiza description quando descriptionPayload é null', function () {
        $root        = cat_root();
        $originalName = $root->description->name;

        cat_repo()->updateCategory($root, ['priority' => 'urgent'], null);

        $this->assertDatabaseHas('solutions_category_description', [
            'category_id' => $root->category_id,
            'name'        => $originalName,
        ]);
    });

    it('retorna categoria com description e parent frescos', function () {
        $root = cat_root();

        $updated = cat_repo()->updateCategory($root, ['priority' => 'urgent'], null);

        expect($updated->relationLoaded('description'))->toBeTrue();
        expect($updated->relationLoaded('parent'))->toBeTrue();
    });

});

// ─── deleteCategory ───────────────────────────────────────────────────────────

describe('CategoryRepository — deleteCategory', function () {

    it('remove a category e a description do banco', function () {
        $root = cat_root();
        $id   = $root->category_id;

        cat_repo()->deleteCategory($root);

        $this->assertDatabaseMissing('solutions_category', ['category_id' => $id]);
        $this->assertDatabaseMissing('solutions_category_description', ['category_id' => $id]);
    });

});

// ─── categoryHasChildren ─────────────────────────────────────────────────────

describe('CategoryRepository — categoryHasChildren', function () {

    it('retorna true quando a categoria possui subcategorias', function () {
        $root  = cat_root();
        $child = cat_child($root);

        expect(cat_repo()->categoryHasChildren($root))->toBeTrue();
    });

    it('retorna false quando a categoria não possui subcategorias', function () {
        $root = cat_root();

        expect(cat_repo()->categoryHasChildren($root))->toBeFalse();
    });

});

// ─── categoryHasSolutions ────────────────────────────────────────────────────

describe('CategoryRepository — categoryHasSolutions', function () {

    it('retorna true quando a categoria possui soluções vinculadas', function () {
        $root = cat_root();

        DB::table('solutions')->insert([
            'category_id'        => $root->category_id,
            'author_id'          => 1,
            'title'              => 'Solução Teste',
            'content'            => 'Conteúdo',
            'searchable_content' => 'Conteúdo',
            'sort_order'         => 0,
            'background'         => '',
            'likes'              => 0,
            'dislikes'           => 0,
            'views'              => 0,
            'status'             => 1,
            'uploads'            => '[]',
            'tags'               => '',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        expect(cat_repo()->categoryHasSolutions($root))->toBeTrue();
    });

    it('retorna false quando a categoria não possui soluções', function () {
        $root = cat_root();

        expect(cat_repo()->categoryHasSolutions($root))->toBeFalse();
    });

});
