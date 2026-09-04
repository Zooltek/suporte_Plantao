<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AtendimentoRepositoryInterface;
use App\Models\Helpdesk\Ticketit\Category;
use App\Models\Solution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AtendimentoRepository implements AtendimentoRepositoryInterface
{
    protected const CACHE_TTL = 86400;

    /**
     * {@inheritDoc}
     */
    public function getRootCategories(): Collection
    {
        return Cache::remember('helpdesk_root_categories', self::CACHE_TTL, function () {
            return Category::where('parent_id', '0')->get();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenByParent(int $parentId): Collection
    {
        return Cache::remember("helpdesk_categories_children_{$parentId}", self::CACHE_TTL, function () use ($parentId) {
            return Category::where('parent_id', $parentId)->get();
        });
    }

    /**
     * {@inheritDoc}
     */
    public function searchSolutions(string $query): Collection
    {
        return Solution::where('status', 1)
            ->where(function ($q) use ($query) {
                $q->where('title', 'LIKE', "%{$query}%")
                  ->orWhere('content', 'LIKE', "%{$query}%");
            })
            ->get();
    }
}
