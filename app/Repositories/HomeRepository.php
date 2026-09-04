<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HomeRepositoryInterface;
use App\Models\Helpdesk\Ticketit\Category;
use App\Models\Solution;
use Illuminate\Database\Eloquent\Collection;

class HomeRepository implements HomeRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getRootCategories(): Collection
    {
        return Category::where('parent_id', 0)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getActiveSolutions(): Collection
    {
        return Solution::where('status', 1)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getLatestSolutions(int $limit = 4): Collection
    {
        return Solution::where('status', 1)
            ->orderBy('sort_order', 'DESC')
            ->limit($limit)
            ->get();
    }
}
