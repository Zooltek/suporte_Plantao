<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\KnowledgeBaseRepositoryInterface;
use App\Models\Category;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Collection;

class KnowledgeBaseRepository implements KnowledgeBaseRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getVisibleRootCategories(): Collection
    {
        return Category::where([
            'parent_id' => 0,
            'visible'   => 1,
            'status'    => 1,
        ])->orderBy('sort_order')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getRelatedSolutions(Solution $solution, int $limit = 5): Collection
    {
        return Solution::where('status', 1)
            ->where('id', '!=', $solution->id)
            ->where(function ($q) use ($solution) {
                if (!empty($solution->tags)) {
                    $q->where('tags', 'LIKE', "%{$solution->tags}%");
                }
            })
            ->limit($limit)
            ->get();
    }
}
