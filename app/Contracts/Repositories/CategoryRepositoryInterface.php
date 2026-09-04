<?php

namespace App\Contracts\Repositories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

interface CategoryRepositoryInterface
{
    public function getAllCategories(): Collection;

    public function getParentCategories(): Collection;

    public function findCategory(int $id): ?Category;

    public function createCategory(array $categoryData, array $descriptionData): Category;

    public function updateCategory(Category $category, array $payload, ?array $descriptionPayload): Category;

    public function deleteCategory(Category $category): void;

    public function categoryHasChildren(Category $category): bool;

    public function categoryHasSolutions(Category $category): bool;

    public function categoryHasTickets(Category $category): bool;
}
