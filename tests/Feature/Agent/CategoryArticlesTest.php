<?php

use App\Models\Category;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\User;

// ─── Helper local ─────────────────────────────────────────────────────────────

/**
 * Cria um artigo de knowledge diretamente, com category_id explícito.
 */
function ca_article(int $categoryId, array $attrs = []): KnowledgeArticle
{
    $author = User::factory()->agent()->create();

    return KnowledgeArticle::create(array_merge([
        'author_id'  => $author->id,
        'category_id' => $categoryId,
        'title'      => 'Artigo Teste',
        'problem'    => 'Descrição do problema',
        'solution'   => 'Solução aplicada',
        'tags'       => 'teste, wiki',
        'visibility' => 'internal',
        'active'     => true,
    ], $attrs));
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('CategoryController — getArticles', function () {

    it('retorna 200 com artigos da categoria solicitada', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        ca_article($cat->category_id, ['title' => 'Artigo da Categoria']);

        $this->getJson(route('agent.settings.categories.articles', $cat->category_id))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Artigo da Categoria']);
    });

    it('não retorna artigos de outras categorias', function () {
        actingAsAgent();
        $cat   = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        $other = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        ca_article($cat->category_id,   ['title' => 'Artigo Correto']);
        ca_article($other->category_id, ['title' => 'Artigo de Outra Categoria']);

        $this->getJson(route('agent.settings.categories.articles', $cat->category_id))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Artigo Correto'])
            ->assertJsonMissing(['title' => 'Artigo de Outra Categoria']);
    });

    it('não retorna artigos inativos', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        ca_article($cat->category_id, ['title' => 'Artigo Ativo',   'active' => true]);
        ca_article($cat->category_id, ['title' => 'Artigo Inativo', 'active' => false]);

        $this->getJson(route('agent.settings.categories.articles', $cat->category_id))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Artigo Ativo'])
            ->assertJsonMissing(['title' => 'Artigo Inativo']);
    });

    it('retorna no máximo 10 artigos', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        for ($i = 1; $i <= 15; $i++) {
            ca_article($cat->category_id, ['title' => "Artigo #{$i}"]);
        }

        $response = $this->getJson(route('agent.settings.categories.articles', $cat->category_id));

        $response->assertOk();
        expect(count($response->json()))->toBeLessThanOrEqual(10);
    });

    it('retorna array vazio quando não há artigos na categoria', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $this->getJson(route('agent.settings.categories.articles', $cat->category_id))
            ->assertOk()
            ->assertJson([]);
    });

    it('retorna os campos id, title, problem, solution, tags', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        ca_article($cat->category_id);

        $response = $this->getJson(route('agent.settings.categories.articles', $cat->category_id));

        $response->assertOk();
        $article = $response->json(0);

        expect($article)->toHaveKeys(['id', 'title', 'problem', 'solution', 'tags']);
    });

    it('não expõe campos sensíveis como author_id ou visibility', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        ca_article($cat->category_id);

        $response = $this->getJson(route('agent.settings.categories.articles', $cat->category_id));

        $response->assertOk();
        $article = $response->json(0);

        expect($article)->not->toHaveKey('author_id');
        expect($article)->not->toHaveKey('visibility');
    });

    it('admin também acessa o endpoint com sucesso', function () {
        actingAsAdmin();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
        ca_article($cat->category_id, ['title' => 'Artigo Visível para Admin']);

        $this->getJson(route('agent.settings.categories.articles', $cat->category_id))
            ->assertOk()
            ->assertJsonFragment(['title' => 'Artigo Visível para Admin']);
    });

    it('usuário não autenticado é redirecionado', function () {
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $this->get(route('agent.settings.categories.articles', $cat->category_id))
            ->assertRedirect();
    });

    it('retorna artigos ordenados do mais recente para o mais antigo', function () {
        actingAsAgent();
        $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);

        $antigo  = ca_article($cat->category_id, ['title' => 'Artigo Antigo']);
        $recente = ca_article($cat->category_id, ['title' => 'Artigo Recente']);

        // Força timestamps distintos para garantir a ordenação
        $antigo->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
        $recente->forceFill(['created_at' => now()])->saveQuietly();

        $response = $this->getJson(route('agent.settings.categories.articles', $cat->category_id));

        $response->assertOk();
        $titles = collect($response->json())->pluck('title');

        expect($titles->first())->toBe('Artigo Recente');
        expect($titles->last())->toBe('Artigo Antigo');
    });

});
