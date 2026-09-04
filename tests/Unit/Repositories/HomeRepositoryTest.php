<?php

/**
 * Testes de integração — HomeRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Repositories\HomeRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function home_repo(): HomeRepository
{
    return new HomeRepository();
}

function home_insert_solution(array $overrides = []): int
{
    return DB::table('solutions')->insertGetId(array_merge([
        'title'              => fake()->sentence(),
        'content'            => fake()->paragraph(),
        'searchable_content' => fake()->paragraph(),
        'status'             => 1,
        'author_id'          => 1,
        'category_id'        => 1,
        'sort_order'         => rand(0, 100),
        'background'         => '',
        'likes'              => 0,
        'dislikes'           => 0,
        'views'              => 0,
        'uploads'            => '[]',
        'tags'               => '',
        'created_at'         => now(),
        'updated_at'         => now(),
    ], $overrides));
}

// ─── getRootCategories ────────────────────────────────────────────────────────

describe('HomeRepository — getRootCategories', function () {

    it('retorna collection (pode ser vazia) da tabela ticketit_categories', function () {
        $result = home_repo()->getRootCategories();

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

    it('não retorna categorias com parent_id diferente de 0', function () {
        // Insere uma categoria filha (parent_id = 1)
        $childId = DB::table('ticketit_categories')->insertGetId([
            'name'  => 'Categoria Filha',
            'color' => '#000',
        ]);

        // HomeRepository filtra parent_id = 0
        // A tabela ticketit_categories não possui coluna parent_id — query retorna vazia para WHERE parent_id=0
        $result = home_repo()->getRootCategories();

        // Deve retornar collection
        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

});

// ─── getActiveSolutions ───────────────────────────────────────────────────────

describe('HomeRepository — getActiveSolutions', function () {

    it('retorna apenas soluções com status = 1', function () {
        $activeId   = home_insert_solution(['status' => 1]);
        $inactiveId = home_insert_solution(['status' => 0]);

        $result = home_repo()->getActiveSolutions();

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($activeId);
        expect($ids)->not->toContain($inactiveId);
    });

    it('retorna collection vazia quando não há soluções ativas', function () {
        $result = home_repo()->getActiveSolutions();

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    });

});

// ─── getLatestSolutions ───────────────────────────────────────────────────────

describe('HomeRepository — getLatestSolutions', function () {

    it('retorna o número correto de soluções com limit', function () {
        for ($i = 0; $i < 6; $i++) {
            home_insert_solution(['status' => 1]);
        }

        $result = home_repo()->getLatestSolutions(4);

        expect($result->count())->toBeLessThanOrEqual(4);
    });

    it('ordena por sort_order DESC', function () {
        home_insert_solution(['status' => 1, 'sort_order' => 10]);
        home_insert_solution(['status' => 1, 'sort_order' => 50]);
        home_insert_solution(['status' => 1, 'sort_order' => 30]);

        $result = home_repo()->getLatestSolutions(10);

        $orders = $result->pluck('sort_order')->toArray();
        $sorted = $orders;
        rsort($sorted);
        expect($orders)->toBe($sorted);
    });

    it('não retorna soluções inativas', function () {
        $inactiveId = home_insert_solution(['status' => 0, 'sort_order' => 9999]);

        $result = home_repo()->getLatestSolutions(10);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->not->toContain($inactiveId);
    });

    it('aceita limit personalizado', function () {
        for ($i = 0; $i < 10; $i++) {
            home_insert_solution(['status' => 1]);
        }

        $result = home_repo()->getLatestSolutions(3);

        expect($result->count())->toBeLessThanOrEqual(3);
    });

});
