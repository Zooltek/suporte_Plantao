<?php

namespace App\Repositories;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class CategoryRepository implements CategoryRepositoryInterface
{
    public function getAllCategories(): Collection
    {
        return Category::with(['description', 'parent.description', 'children.description'])
            ->orderBy('priority', 'desc')
            ->orderBy('category_id')
            ->get();
    }

    public function getParentCategories(): Collection
    {
        return Category::query()
            ->whereNull('parent_id')
            ->orWhere('parent_id', 0)
            ->with('description')
            ->orderBy('category_id')
            ->get();
    }

    public function findCategory(int $id): ?Category
    {
        return Category::query()->find($id);
    }

    public function createCategory(array $categoryData, array $descriptionData): Category
    {
        return DB::transaction(function () use ($categoryData, $descriptionData) {
            $category = Category::create($categoryData);

            $category->description()->create($descriptionData);

            return $category->load('description', 'parent.description');
        });
    }

    public function updateCategory(Category $category, array $payload, ?array $descriptionPayload): Category
    {
        return DB::transaction(function () use ($category, $payload, $descriptionPayload) {
            $category->update($payload);

            if ($descriptionPayload !== null && $category->description) {
                $category->description->update($descriptionPayload);
            }

            return $category->fresh(['description', 'parent.description']);
        });
    }

    public function deleteCategory(Category $category): void
    {
        DB::transaction(function () use ($category) {
            $category->description()?->delete();
            $category->delete();
        });
    }

    public function categoryHasChildren(Category $category): bool
    {
        return $category->children()->exists();
    }

    public function categoryHasSolutions(Category $category): bool
    {
        return $category->solutions()->exists();
    }

    public function categoryHasTickets(Category $category): bool
    {
        $id = $category->getKey();

        return DB::table('ticketit')
            ->where('category_id', $id)
            ->orWhere('sub_category_id', $id)
            ->exists() || $category->tickets()->exists();
    }
}
