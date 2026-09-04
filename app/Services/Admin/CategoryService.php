<?php

namespace App\Services\Admin;

use App\Contracts\Repositories\CategoryRepositoryInterface;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
    ) {}

    public function getAllCategories()
    {
        return $this->categoryRepository->getAllCategories();
    }

    public function getParentCategories()
    {
        return $this->categoryRepository->getParentCategories();
    }

    public function create(array $data): Category
    {
        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);

        return $parentId === null
            ? $this->createRootCategory($data)
            : $this->createSubcategory($data);
    }

    public function createRootCategory(array $data): Category
    {
        $categoryData = [
            'parent_id' => 0,
            'sort_order' => 0,
            'status' => 1,
            'visible' => 1,
            'ticket_category_id' => 1,
            'profile' => 0,
            'header' => 1,
            'priority' => $data['priority'] ?? 'low',
            'department_id' => $this->normalizeDepartmentId($data['department_id'] ?? null),
        ];

        $descriptionData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permalink' => $this->resolvePermalink($data['permalink'] ?? null, $data['name']),
        ];

        return $this->categoryRepository->createCategory($categoryData, $descriptionData);
    }

    public function createSubcategory(array $data): Category
    {
        $parentId = $this->normalizeParentId($data['parent_id'] ?? null);

        if ($parentId === null) {
            throw new \InvalidArgumentException('Selecione uma categoria válida para criar a subcategoria.');
        }

        $parent = $this->resolveValidParentCategory($parentId);
        $this->ensureParentCanReceiveChildren($parent);

        $categoryData = [
            'parent_id' => (int) $parent->getKey(),
            'sort_order' => 0,
            'status' => 1,
            'visible' => 1,
            'ticket_category_id' => 1,
            'profile' => 0,
            'header' => 1,
            'priority' => $data['priority'] ?? 'low',
            'department_id' => $this->resolveSubcategoryDepartmentId($data, $parent),
        ];

        $descriptionData = [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'permalink' => $this->resolvePermalink($data['permalink'] ?? null, $data['name']),
        ];

        return $this->categoryRepository->createCategory($categoryData, $descriptionData);
    }

    public function update(Category $category, array $data): Category
    {
        $categoryPayload = ['priority' => $data['priority'] ?? $category->priority];

        if (array_key_exists('department_id', $data)) {
            $categoryPayload['department_id'] = $this->normalizeDepartmentId($data['department_id']);
        }

        if (array_key_exists('parent_id', $data)) {
            $parentId = $this->normalizeParentId($data['parent_id']);

            if ($parentId !== null) {
                if ($this->categoryRepository->categoryHasChildren($category)) {
                    throw new \InvalidArgumentException('Não é possível transformar em subcategoria uma categoria que já possui subcategorias.');
                }

                $parent = $this->resolveValidParentCategory($parentId, (int) $category->getKey());
                $this->ensureParentCanReceiveChildren($parent);
            }

            $categoryPayload['parent_id'] = $parentId ?? 0;
        }

        $descriptionPayload = null;

        if ($category->description && isset($data['name'])) {
            $descriptionPayload = [
                'name' => $data['name'],
                'description' => $data['description'] ?? $category->description->description,
                'permalink' => $this->resolvePermalink($data['permalink'] ?? null, $data['name']),
            ];
        } elseif ($category->description && isset($data['permalink'])) {
            $descriptionPayload = [
                'permalink' => $this->resolvePermalink($data['permalink'], $category->description->name),
            ];
        }

        return $this->categoryRepository->updateCategory($category, $categoryPayload, $descriptionPayload);
    }

    public function delete(Category $category): void
    {
        if ($this->categoryRepository->categoryHasChildren($category)) {
            throw new \InvalidArgumentException('Não é possível excluir uma categoria que possui subcategorias.');
        }

        if ($this->categoryRepository->categoryHasSolutions($category)) {
            throw new \InvalidArgumentException('Não é possível excluir uma categoria que possui soluções vinculadas.');
        }

        if ($this->categoryRepository->categoryHasTickets($category)) {
            throw new \InvalidArgumentException('Não é possível excluir uma categoria que possui chamados vinculados. Altere os chamados ou desative a visibilidade da categoria.');
        }

        $this->categoryRepository->deleteCategory($category);
    }

    public function categoryHasChildren(Category $category): bool
    {
        return $this->categoryRepository->categoryHasChildren($category);
    }

    public function categoryHasSolutions(Category $category): bool
    {
        return $this->categoryRepository->categoryHasSolutions($category);
    }

    public function categoryHasTickets(Category $category): bool
    {
        return $this->categoryRepository->categoryHasTickets($category);
    }

    private function normalizeParentId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        $parentId = (int) $value;

        return $parentId > 0 ? $parentId : null;
    }

    private function resolvePermalink(mixed $permalink, string $fallbackName): string
    {
        $value = trim((string) ($permalink ?? ''));

        return $value !== '' ? $value : Str::slug($fallbackName);
    }

    private function resolveValidParentCategory(int $parentId, ?int $selfCategoryId = null): Category
    {
        $parent = $this->categoryRepository->findCategory($parentId);

        if (! $parent) {
            throw new \InvalidArgumentException('Categoria inválida.');
        }

        if ($selfCategoryId !== null && $selfCategoryId === (int) $parent->getKey()) {
            throw new \InvalidArgumentException('Uma categoria não pode ser pai dela mesma.');
        }

        return $parent;
    }

    private function ensureParentCanReceiveChildren(Category $parent): void
    {
        if ($this->normalizeParentId($parent->parent_id) !== null) {
            throw new \InvalidArgumentException('Subcategorias não podem ter outras subcategorias.');
        }
    }

    private function normalizeDepartmentId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 0 || $value === '0') {
            return null;
        }

        $departmentId = (int) $value;

        return $departmentId > 0 ? $departmentId : null;
    }

    private function resolveSubcategoryDepartmentId(array $data, Category $parent): ?int
    {
        if (array_key_exists('department_id', $data)) {
            return $this->normalizeDepartmentId($data['department_id']);
        }

        return $this->normalizeDepartmentId($parent->department_id);
    }
}
