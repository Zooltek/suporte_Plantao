<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Knowledge\KnowledgeArticle;
use Illuminate\Http\JsonResponse;

/**
 * Mantém apenas o endpoint JSON de filhos de categoria,
 * consumido pelo formulário de criação/edição de tickets.
 * O CRUD completo de categorias é gerenciado em /admin/categories.
 */
class CategoryController extends Controller
{
    public function getChildren(int $id): JsonResponse
    {
        $children = Category::where('parent_id', $id)
            ->with('description')
            ->orderBy('category_id')
            ->get()
            ->map(fn($cat) => [
                'id'       => $cat->category_id,
                'name'     => $cat->description?->name ?? $cat->name,
                'priority' => $cat->priority,
            ]);

        return response()->json($children);
    }

    public function getArticles(int $id): JsonResponse
    {
        $articles = KnowledgeArticle::active()
            ->where('category_id', $id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'problem', 'solution', 'tags']);

        return response()->json($articles);
    }
}
