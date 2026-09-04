<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HelpdeskCategoryRepositoryInterface;
use App\Models\Category;
use App\Models\CategoryDescription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HelpdeskCategoryRepository implements HelpdeskCategoryRepositoryInterface
{
    protected const CACHE_KEY = 'helpdesk_category_hierarchy';

    /**
     * {@inheritDoc}
     */
    public function getRootByTicketCategory(int $ticketCategoryId): Collection
    {
        return Category::where([
            'ticket_category_id' => $ticketCategoryId,
            'parent_id'          => 0,
        ])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function storeOrUpdate(array $data, ?int $id = null): Category
    {
        return DB::transaction(function () use ($data, $id) {
            $category = $id
                ? Category::where('category_id', $id)->firstOrFail()
                : new Category();

            $category->parent_id          = $data['parent'] ?? 0;
            $category->sort_order         = $data['sort'];
            $category->ticket_category_id = $data['setor'];
            $category->status             = (int) ($data['status'] ?? 0);
            $category->visible            = (int) ($data['visible'] ?? 0);
            $category->header             = (int) ($data['header'] ?? 0);
            $category->save();

            $desc              = CategoryDescription::firstOrNew(['category_id' => $category->category_id]);
            $desc->name        = $data['name'];
            $desc->description = $data['desc'];
            $desc->image       = $data['image'] ?? null;
            $desc->html_header = $data['html'] ?? null;
            $desc->save();

            Cache::forget(self::CACHE_KEY . '_setor_' . $category->ticket_category_id);

            return $category;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getChildrenWithDescription(int $parentId): Collection
    {
        return Category::where('parent_id', $parentId)->with('description')->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $id): Category
    {
        return Category::where('category_id', $id)->firstOrFail();
    }
}
