<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\AtendimentoService;
use App\Http\Requests\AtendimentoFindCategoryRequest;
use App\Models\Helpdesk\Ticketit\Category;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class AtendimentoController extends Controller
{
    public function __construct(
        protected AtendimentoService $atendimentoService
    ) {
        $this->middleware('auth');
    }

    /**
     * Exibe a página inicial de atendimento.
     */
    public function index(): View
    {
        $categories = $this->atendimentoService->getRootCategories();

        return view('helpdesk.atendimento.index', compact('categories'));
    }

    /**
     * Gerencia a navegação dinâmica de categorias via AJAX.
     */
    public function findCategoryChild(AtendimentoFindCategoryRequest $request): View
    {
        $parentId = $request->integer('category_id');
        $categoryName = $request->input('category_name');
        $isAgent = $request->has('agent');

        $categories = $this->atendimentoService->getChildren($parentId);

        // Se houver subcategorias, retorna o select correspondente
        if ($categories->isNotEmpty()) {
            $view = $isAgent
                ? 'helpdesk.atendimento.partials.category_agent_select'
                : 'helpdesk.atendimento.partials.category_select';

            return view($view, [
                'category_name' => $categoryName,
                'categories'    => $categories
            ]);
        }

        // Se NÃO houver subcategorias, chegamos ao final da árvore (Folha)
        if ($isAgent) {
            $category = Category::findOrFail($parentId);
            
            return view('helpdesk.atendimento.partials.category_html', [
                'category_name' => $categoryName,
                'category_id'   => $parentId,
                'category'      => $category
            ]);
        }

        // Fluxo para usuários comuns: Busca soluções relacionadas
        Log::info("Busca de atendimento por categoria: '{$categoryName}'");

        $results = $this->atendimentoService->searchSolutions($categoryName);

        return view('helpdesk.atendimento.partials.category_related', [
            'results'       => $results,
            'search_query'  => $categoryName,
            'category_name' => $categoryName,
            'category_id'   => $parentId
        ]);
    }
}
