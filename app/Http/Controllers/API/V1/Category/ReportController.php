<?php

namespace App\Http\Controllers\API\V1\Category;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Ticket\Ticket;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class ReportController extends Controller
{
    /**
     * retorna a contagem de tickets por subcategoria dentro de uma categoria pai.
     * * @param Request $request
     * @return JsonResponse
     */
    public function categoryCountByParent(Request $request): JsonResponse
    {
        $start = Carbon::parse($request->string('start', now()->toDateTimeString()))->startOfDay();
        $end = Carbon::parse($request->string('end', now()->toDateTimeString()))->endOfDay();
        $parentId = $request->integer('parent_id');

        // query otimizada com withCount e Eager Loading
        $categories = Category::query()
            ->with(['description']) // carrega a relação para evitar lazy loading no loop
            ->where('parent_id', $parentId)
            ->withCount(['tickets' => function ($query) use ($start, $end) {
                $query->whereBetween('created_at', [$start, $end])
                      ->whereColumn('sub_category_id', 'categories.category_id');
            }])
            ->get();

        $data = $categories->map(function (Category $category) {
            return [
                'category_id' => $category->category_id,
                'name'        => $category->description?->name ?? 'Sem Nome',
                'count'       => $category->tickets_count,
                'parent_id'   => $category->parent_id,
            ];
        })
        ->sortByDesc('count')
        ->values();

        return response()->json($data);
    }
}
