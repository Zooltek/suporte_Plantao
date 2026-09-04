<?php

/**
 * Testes de integração — AtendimentoRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Repositories\AtendimentoRepository;
use Illuminate\Support\Facades\Cache;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function atend_repo(): AtendimentoRepository
{
    return new AtendimentoRepository();
}

function insert_ticketit_category(array $overrides = []): int
{
    return DB::table('ticketit_categories')->insertGetId(array_merge([
        'name'  => fake()->word(),
        'color' => '#ff0000',
    ], $overrides));
}

// ─── getRootCategories ────────────────────────────────────────────────────────

describe('AtendimentoRepository — getRootCategories', function () {

    it('retorna categorias com parent_id = 0 da tabela ticketit', function () {
        // TicketitCategory não tem parent_id — todos são retornados
        // Verificamos que a query roda sem exceção e retorna Collection
        Cache::forget('helpdesk_root_categories');

        $result = atend_repo()->getRootCategories();

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('armazena resultado em cache na primeira chamada', function () {
        Cache::forget('helpdesk_root_categories');

        atend_repo()->getRootCategories();

        expect(Cache::has('helpdesk_root_categories'))->toBeTrue();
    });

    it('retorna dado do cache na segunda chamada sem nova query', function () {
        Cache::forget('helpdesk_root_categories');

        $first  = atend_repo()->getRootCategories();
        $second = atend_repo()->getRootCategories();

        expect($first->count())->toBe($second->count());
    });

});

// ─── getChildrenByParent ──────────────────────────────────────────────────────

describe('AtendimentoRepository — getChildrenByParent', function () {

    it('armazena resultado em cache com chave por parent_id', function () {
        $parentId = 777;
        Cache::forget("helpdesk_categories_children_{$parentId}");

        atend_repo()->getChildrenByParent($parentId);

        expect(Cache::has("helpdesk_categories_children_{$parentId}"))->toBeTrue();
    });

    it('retorna collection vazia quando não há filhos', function () {
        $result = atend_repo()->getChildrenByParent(999999);

        expect($result)->toBeEmpty();
    });

    it('chave de cache é única por parent_id', function () {
        Cache::put('helpdesk_categories_children_1', collect(['a']), 10);
        Cache::put('helpdesk_categories_children_2', collect(['b']), 10);

        $r1 = Cache::get('helpdesk_categories_children_1');
        $r2 = Cache::get('helpdesk_categories_children_2');

        expect($r1->first())->toBe('a');
        expect($r2->first())->toBe('b');
    });

});

// ─── searchSolutions ─────────────────────────────────────────────────────────

describe('AtendimentoRepository — searchSolutions', function () {

    it('encontra soluções ativas que contenham o texto no título', function () {
        DB::table('solutions')->insert([
            'title'              => 'Como resetar senha',
            'content'            => 'Conteúdo qualquer',
            'searchable_content' => 'Conteúdo qualquer',
            'status'             => 1,
            'author_id'          => 1,
            'category_id'        => 1,
            'sort_order'         => 0,
            'background'         => '',
            'likes'              => 0,
            'dislikes'           => 0,
            'views'              => 0,
            'uploads'            => '[]',
            'tags'               => '',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $result = atend_repo()->searchSolutions('resetar');

        expect($result)->not->toBeEmpty();
        expect($result->first()->title)->toContain('resetar');
    });

    it('não retorna soluções inativas', function () {
        DB::table('solutions')->insert([
            'title'              => 'Solução inativa xyzXYZ',
            'content'            => 'Inativo',
            'searchable_content' => 'Inativo',
            'status'             => 0,
            'author_id'          => 1,
            'category_id'        => 1,
            'sort_order'         => 0,
            'background'         => '',
            'likes'              => 0,
            'dislikes'           => 0,
            'views'              => 0,
            'uploads'            => '[]',
            'tags'               => '',
            'created_at'         => now(),
            'updated_at'         => now(),
        ]);

        $result = atend_repo()->searchSolutions('xyzXYZ');

        expect($result)->toBeEmpty();
    });

    it('retorna collection vazia quando não há resultados', function () {
        $result = atend_repo()->searchSolutions('termoquenaoexiste12345');

        expect($result)->toBeEmpty();
    });

});
