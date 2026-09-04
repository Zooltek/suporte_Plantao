<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\API\V1\StoreCategoryRequest;
use App\Http\Requests\Admin\API\V1\UpdateCategoryRequest;
use App\Models\Category;
use App\Models\Department;
use App\Services\Admin\CategoryService;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}

    /**
     * Exibe a view principal de categorias
     */
    public function index(): View
    {
        // Web view: retorna apenas categorias raiz com children eager loaded
        $categories = Category::query()
            ->where(function ($query): void {
                $query->whereNull('parent_id')->orWhere('parent_id', 0);
            })
            ->with(['description', 'department', 'children.description', 'children.department'])
            ->orderBy('priority', 'desc')
            ->orderBy('category_id')
            ->get();

        return view('admin.categories.index', [
            'categories' => $categories,
            'parents' => $this->categoryService->getParentCategories(),
            'departments' => Department::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCategoryRequest $request)
    {
        $this->categoryService->create($request->validated());

        return response()->json(['success' => true, 'message' => 'Categoria criada com sucesso!']);
    }

    public function show($id)
    {
        $category = Category::with('description', 'parent.description')->findOrFail($id);

        return response()->json(['category' => $category]);
    }

    public function update(UpdateCategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);
        $this->categoryService->update($category, $request->validated());

        return response()->json(['success' => true, 'message' => 'Categoria atualizada com sucesso!']);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $this->categoryService->delete($category);

        return response()->json(['success' => true, 'message' => 'Categoria excluída com sucesso!']);
    }
}
