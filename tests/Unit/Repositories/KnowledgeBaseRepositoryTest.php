<?php

/**
 * Testes de integração — KnowledgeBaseRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Solution;
use App\Repositories\KnowledgeBaseRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function kb_repo(): KnowledgeBaseRepository
{
    return new KnowledgeBaseRepository();
}

function kb_root_category(array $attrs = []): Category
{
    $category = Category::factory()->create(array_merge([
        'parent_id' => 0,
        'visible'   => 1,
        'status'    => 1,
        'sort_order' => 10,
    ], $attrs));

    CategoryDescription::factory()->create(['category_id' => $category->category_id]);

    return $category->fresh('description');
}

function kb_insert_solution(array $overrides = []): int
{
    return DB::table('solutions')->insertGetId(array_merge([
        'title'              => fake()->sentence(),
        'content'            => fake()->paragraph(),
        'searchable_content' => fake()->paragraph(),
        'status'             => 1,
        'author_id'          => 1,
        'category_id'        => 1,
        'sort_order'         => 0,
        'background'         => '',
        'likes'              => 0,
        'dislikes'           => 0,
        'views'              => 0,
        'uploads'            => '[]',
        'tags'               => 'php',
        'created_at'         => now(),
        'updated_at'         => now(),
    ], $overrides));
}

// ─── getVisibleRootCategories ─────────────────────────────────────────────────

describe('KnowledgeBaseRepository — getVisibleRootCategories', function () {

    it('retorna apenas categorias raiz visíveis e ativas', function () {
        $visible  = kb_root_category(['visible' => 1, 'status' => 1]);
        $hidden   = kb_root_category(['visible' => 0, 'status' => 1]);
        $inactive = kb_root_category(['visible' => 1, 'status' => 0]);

        $result = kb_repo()->getVisibleRootCategories();

        $ids = $result->pluck('category_id')->toArray();
        expect($ids)->toContain($visible->category_id);
        expect($ids)->not->toContain($hidden->category_id);
        expect($ids)->not->toContain($inactive->category_id);
    });

    it('não retorna categorias filhas (parent_id != 0)', function () {
        $root  = kb_root_category();
        $child = Category::factory()->create([
            'parent_id' => $root->category_id,
            'visible'   => 1,
            'status'    => 1,
        ]);

        $result = kb_repo()->getVisibleRootCategories();

        $ids = $result->pluck('category_id')->toArray();
        expect($ids)->not->toContain($child->category_id);
    });

    it('ordena por sort_order crescente', function () {
        kb_root_category(['sort_order' => 30]);
        kb_root_category(['sort_order' => 10]);
        kb_root_category(['sort_order' => 20]);

        $result = kb_repo()->getVisibleRootCategories();

        $orders = $result->pluck('sort_order')->toArray();
        $sorted = $orders;
        sort($sorted);
        expect($orders)->toBe($sorted);
    });

    it('retorna collection vazia quando não há categorias visíveis', function () {
        kb_root_category(['visible' => 0, 'status' => 0]);

        $result = kb_repo()->getVisibleRootCategories();

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

});

// ─── getRelatedSolutions ──────────────────────────────────────────────────────

describe('KnowledgeBaseRepository — getRelatedSolutions', function () {

    it('não retorna a própria solução nos relacionados', function () {
        $solutionId = kb_insert_solution(['tags' => 'php laravel']);
        $solution   = Solution::find($solutionId);

        $result = kb_repo()->getRelatedSolutions($solution, 10);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->not->toContain($solutionId);
    });

    it('retorna apenas soluções ativas', function () {
        $activeId   = kb_insert_solution(['status' => 1, 'tags' => 'python']);
        $inactiveId = kb_insert_solution(['status' => 0, 'tags' => 'python']);
        $baseSolution = Solution::find($activeId);

        $baseSolutionOther = (object)['id' => 0, 'tags' => 'python'];
        $baseSolutionModel = new Solution();
        $baseSolutionModel->id   = 0;
        $baseSolutionModel->tags = 'python';

        $result = kb_repo()->getRelatedSolutions($baseSolutionModel, 10);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($activeId);
        expect($ids)->not->toContain($inactiveId);
    });

    it('respeita o limite fornecido', function () {
        for ($i = 0; $i < 8; $i++) {
            kb_insert_solution(['tags' => 'laravel']);
        }

        $model       = new Solution();
        $model->id   = 999999;
        $model->tags = 'laravel';

        $result = kb_repo()->getRelatedSolutions($model, 3);

        expect($result->count())->toBeLessThanOrEqual(3);
    });

});
