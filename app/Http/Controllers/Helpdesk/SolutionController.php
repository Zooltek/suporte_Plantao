<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\KnowledgeBaseService;
use App\Models\Solution;
use App\Models\Helpdesk\Ticketit\Category;
use App\Models\Author;
use App\Http\Requests\PrepareSolutionStoreRequest;
use App\Http\Requests\PrepareSolutionUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class SolutionController extends Controller
{
    public function __construct(
        protected KnowledgeBaseService $kbService
    ) {
        $this->middleware('auth');
    }

    public function index(int $id = -1): View
    {
        $categories = Category::where([
            'status' => 1,
            'visible' => 1,
            'parent_id' => 0,
            'ticket_category_id' => 1
        ])->orderBy('sort_order')->get();

        return view('helpdesk.index', [
            'categories' => $categories,
            'sub_categories' => Category::where('status', 1)->orderBy('sort_order')->get(),
            'category_id' => $id
        ]);
    }

    public function category(int $id = -1): View
    {
        $category = Category::findOrFail($id);
        
        return view('helpdesk.category.index', [
            'category' => $category,
            'category_childs' => $category->getChildOrdered('name'),
            'solutions' => Solution::where('status', '1')->orderBy('likes', 'desc')->get(),
            'author' => Author::all(),
            'tree' => json_encode($this->kbService->getCategoryTree($id)),
            'category_id' => $id
        ]);
    }

    public function show(int $id): View
    {
        $solution = Solution::where('status', 1)->findOrFail($id);
        
        // Incremento de views usando método atômico do Laravel
        $solution->increment('views');

        $related = Solution::where('status', 1)
            ->where('id', '!=', $id)
            ->search($solution->tags)
            ->limit(5)
            ->get();

        return view('helpdesk.solution.show', [
            'solution' => $solution,
            'related_articles' => $related,
            'query' => $solution->tags
        ]);
    }

    public function store(PrepareSolutionStoreRequest $request): RedirectResponse
    {
        $solution = new Solution($request->validated());
        $solution->author_id = auth()->id();
        $solution->save();

        return redirect()->route('solution.new')
            ->with('status', 'Artigo criado com sucesso.');
    }

    public function update(PrepareSolutionUpdateRequest $request, int $id): RedirectResponse
    {
        $solution = Solution::findOrFail($id);
        $solution->update($request->validated());

        return redirect()->route('solution.show', $id)
            ->with('status', "Artigo atualizado com sucesso");
    }

    public function edit(int $id): View
    {
        return view('helpdesk.solution.edit', [
            'solution' => Solution::findOrFail($id),
            'categories' => $this->kbService->getFlattenedHierarchy()
        ]);
    }

    public function disable(int $id): RedirectResponse
    {
        Solution::where('id', $id)->update(['status' => 0]);
        return redirect()->route('solution')->with('status', "Artigo desativado.");
    }
}
