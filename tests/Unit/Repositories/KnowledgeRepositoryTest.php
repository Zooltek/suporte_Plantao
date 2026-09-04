<?php

use App\Models\Category;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\User;
use App\Repositories\KnowledgeRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function kr_repo(): KnowledgeRepository
{
    return new KnowledgeRepository();
}

function kr_user(): User
{
    return User::factory()->agent()->create();
}

function kr_category(): Category
{
    return Category::factory()->create(['parent_id' => 0]);
}

function kr_article(array $attrs = []): KnowledgeArticle
{
    $user     = kr_user();
    $category = kr_category();

    return KnowledgeArticle::create(array_merge([
        'author_id'  => $user->id,
        'ticket_id'  => null,
        'category_id' => $category->category_id,
        'title'      => 'Artigo de Teste',
        'problem'    => 'Descrição do problema',
        'solution'   => 'Solução do problema',
        'tags'       => 'teste, artigo',
        'visibility' => 'internal',
        'active'     => true,
    ], $attrs));
}

// ─── paginate ─────────────────────────────────────────────────────────────────

describe('KnowledgeRepository — paginate', function () {

    it('retorna apenas artigos ativos', function () {
        kr_article(['active' => true]);
        kr_article(['active' => false]);

        $result = kr_repo()->paginate(null, null);

        $ids = $result->pluck('id')->toArray();

        // Verifica que artigos inativos não aparecem
        $inactiveFound = KnowledgeArticle::where('active', false)->pluck('id');
        foreach ($inactiveFound as $id) {
            expect($ids)->not->toContain($id);
        }

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    it('filtra por texto de busca no título', function () {
        kr_article(['title' => 'Erro no módulo fiscal único', 'active' => true]);
        kr_article(['title' => 'Artigo sem relação', 'active' => true]);

        $result = kr_repo()->paginate('fiscal único', null);

        $titles = $result->pluck('title')->toArray();
        expect($titles)->toContain('Erro no módulo fiscal único');
    });

    it('filtra por category_id', function () {
        $categoryA = kr_category();
        $categoryB = kr_category();

        $user = kr_user();

        KnowledgeArticle::create([
            'author_id'  => $user->id,
            'category_id' => $categoryA->category_id,
            'title'      => 'Artigo Cat A',
            'problem'    => 'p',
            'solution'   => 's',
            'active'     => true,
        ]);

        KnowledgeArticle::create([
            'author_id'  => $user->id,
            'category_id' => $categoryB->category_id,
            'title'      => 'Artigo Cat B',
            'problem'    => 'p',
            'solution'   => 's',
            'active'     => true,
        ]);

        $result = kr_repo()->paginate(null, $categoryA->category_id);

        $catIds = $result->pluck('category_id')->unique()->toArray();
        expect($catIds)->toContain($categoryA->category_id);
        expect($catIds)->not->toContain($categoryB->category_id);
    });

    it('carrega as relações author e category', function () {
        kr_article(['active' => true]);

        $result = kr_repo()->paginate(null, null);

        $article = $result->first();
        expect($article->relationLoaded('author'))->toBeTrue();
        expect($article->relationLoaded('category'))->toBeTrue();
    });

    it('pagina com 20 itens por página', function () {
        $result = kr_repo()->paginate(null, null);

        expect($result->perPage())->toBe(20);
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('KnowledgeRepository — create', function () {

    it('persiste um artigo no banco com os dados corretos', function () {
        $user     = kr_user();
        $category = kr_category();

        $article = kr_repo()->create([
            'author_id'   => $user->id,
            'category_id' => $category->category_id,
            'title'       => 'Novo Artigo',
            'problem'     => 'Problema X',
            'solution'    => 'Solução X',
            'active'      => true,
        ]);

        expect($article)->toBeInstanceOf(KnowledgeArticle::class);
        expect($article->exists)->toBeTrue();

        test()->assertDatabaseHas('knowledge_articles', [
            'id'          => $article->id,
            'title'       => 'Novo Artigo',
            'author_id'   => $user->id,
        ]);
    });

    it('retorna uma instância de KnowledgeArticle', function () {
        $user = kr_user();

        $article = kr_repo()->create([
            'author_id' => $user->id,
            'title'     => 'Test',
            'problem'   => 'p',
            'solution'  => 's',
            'active'    => true,
        ]);

        expect($article)->toBeInstanceOf(KnowledgeArticle::class);
    });

});

// ─── incrementViews ───────────────────────────────────────────────────────────

describe('KnowledgeRepository — incrementViews', function () {

    it('incrementa o contador de views no banco', function () {
        $article = kr_article();
        $before  = (int) $article->views;

        kr_repo()->incrementViews($article);

        test()->assertDatabaseHas('knowledge_articles', [
            'id'    => $article->id,
            'views' => $before + 1,
        ]);
    });

    it('atualiza o atributo views no modelo em memória', function () {
        $article = kr_article();
        $before  = (int) $article->views;

        kr_repo()->incrementViews($article);

        expect((int) $article->views)->toBe($before + 1);
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('KnowledgeRepository — delete', function () {

    it('remove o artigo do banco', function () {
        $article = kr_article();
        $id      = $article->id;

        kr_repo()->delete($article);

        test()->assertDatabaseMissing('knowledge_articles', ['id' => $id]);
    });

});
