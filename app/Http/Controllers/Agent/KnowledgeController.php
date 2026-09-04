<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\Agent\Knowledge\KnowledgeStoreRequest;
use App\Models\Category;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Ticket\Ticket;
use App\Services\Knowledge\KnowledgeService;
use App\Services\Notion\NotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KnowledgeController extends Controller
{
    public function __construct(
        private readonly KnowledgeService $knowledgeService,
        private readonly NotionService $notionService,
    ) {}

    public function index(Request $request): View
    {
        if ($request->boolean('refresh')) {
            \Illuminate\Support\Facades\Cache::forget('ticketit_settings_notion');
            \Illuminate\Support\Facades\Cache::forget('notion_articles_all');
        }

        $articles   = $this->knowledgeService->list(
            $request->get('q'),
            $request->integer('category_id') ?: null
        );
        $categories = Category::where('solutions_category.parent_id', 0)
            ->join('solutions_category_description', 'solutions_category.category_id', '=', 'solutions_category_description.category_id')
            ->orderBy('solutions_category_description.name')
            ->select('solutions_category.*', 'solutions_category_description.name')
            ->get();

        $notionConfigured = $this->notionService->isConfigured();
        $notionSettings = $this->notionService->getSettings();
        $notionArticles = $notionConfigured
            ? $this->notionService->getArticles($request->get('q'))
            : [];

        return view('agent.knowledge.index', compact('articles', 'categories', 'notionConfigured', 'notionSettings', 'notionArticles'));
    }

    public function saveNotionSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'api_key' => ['required', 'string', 'max:500'],
            'database_id' => ['nullable', 'string', 'max:255'],
        ]);

        $this->notionService->saveSettings(
            apiKey: $validated['api_key'],
            databaseId: $validated['database_id'] ?? null
        );

        return redirect()->route('agent.knowledge.index', ['tab' => 'notion'])
            ->with('status', 'Configurações do Notion salvas com sucesso!');
    }

    public function testNotionConnection(Request $request)
    {
        $apiKey = $request->input('api_key');
        $databaseId = $request->input('database_id');

        $result = $this->notionService->testConnection($apiKey, $databaseId);

        return response()->json($result);
    }

    public function show(KnowledgeArticle $knowledge): View
    {
        $this->knowledgeService->incrementViews($knowledge);
        $knowledge->load('author', 'category');

        return view('agent.knowledge.show', ['article' => $knowledge]);
    }

    /**
     * Exibe um artigo buscado diretamente do Notion.
     */
    public function showNotion(Request $request, string $pageId): View
    {
        $refresh = $request->boolean('refresh');
        if ($refresh) {
            $cleanId = NotionService::cleanNotionId($pageId) ?? $pageId;
            \Illuminate\Support\Facades\Cache::forget('notion_page_' . $cleanId);
        }

        $article = $this->notionService->getArticle($pageId);

        if (!$article) {
            abort(404, 'Artigo do Notion não encontrado ou inacessível.');
        }

        $navigationTree = $this->notionService->getNavigationTree($refresh);

        return view('agent.knowledge.notion', compact('article', 'navigationTree'));
    }

    /**
     * Exibe formulário de criação, opcionalmente pré-populado a partir de um ticket.
     */
    public function create(Request $request): View
    {
        $ticket     = $request->integer('ticket_id') ? Ticket::find($request->integer('ticket_id')) : null;
        $categories = Category::where('solutions_category.parent_id', 0)
            ->join('solutions_category_description', 'solutions_category.category_id', '=', 'solutions_category_description.category_id')
            ->orderBy('solutions_category_description.name')
            ->select('solutions_category.*', 'solutions_category_description.name')
            ->get();

        return view('agent.knowledge.create', compact('ticket', 'categories'));
    }

    public function store(KnowledgeStoreRequest $request): RedirectResponse
    {
        $article = $this->knowledgeService->create($request->validated());

        return redirect()
            ->route('agent.knowledge.show', $article)
            ->with('status', 'Artigo salvo na EasyWiki com sucesso!');
    }

    public function edit(KnowledgeArticle $knowledge): View
    {
        $categories = Category::where('solutions_category.parent_id', 0)
            ->join('solutions_category_description', 'solutions_category.category_id', '=', 'solutions_category_description.category_id')
            ->orderBy('solutions_category_description.name')
            ->select('solutions_category.*', 'solutions_category_description.name')
            ->get();

        return view('agent.knowledge.edit', [
            'article' => $knowledge,
            'categories' => $categories,
        ]);
    }

    public function update(KnowledgeStoreRequest $request, KnowledgeArticle $knowledge): RedirectResponse
    {
        $article = $this->knowledgeService->update($knowledge, $request->validated());

        return redirect()
            ->route('agent.knowledge.show', $article)
            ->with('status', 'Artigo atualizado com sucesso!');
    }

    /**
     * Upload de imagens e mídias para inserção direta no editor Summernote.
     */
    public function uploadMedia(Request $request)
    {
        $request->validate([
            'image' => ['required', 'file', 'mimes:jpeg,png,jpg,gif,svg,webp', 'max:10240'],
        ]);

        $file = $request->file('image');
        $path = $file->store('knowledge/media', 'public');
        $url  = asset('storage/' . $path);

        return response()->json([
            'url' => $url,
            'filename' => $file->getClientOriginalName(),
        ]);
    }

    public function destroy(KnowledgeArticle $knowledge): RedirectResponse
    {
        $this->authorize('delete', $knowledge);
        $this->knowledgeService->delete($knowledge);

        return redirect()
            ->route('agent.knowledge.index')
            ->with('status', 'Artigo removido.');
    }
}
