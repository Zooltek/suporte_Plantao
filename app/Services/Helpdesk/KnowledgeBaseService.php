<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\KnowledgeBaseRepositoryInterface;
use App\Models\Category;
use App\Models\Solution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class KnowledgeBaseService
{
    public function __construct(
        private readonly KnowledgeBaseRepositoryInterface $repository,
    ) {}

    /**
     * Gera a árvore de categorias formatada para Alpine.js.
     *
     * @param int $selectedId ID da categoria ativa para expansão automática
     */
    public function getCategoryTree(int $selectedId = 0): array
    {
        $cacheKey = "kb_tree_alpine_{$selectedId}";

        return Cache::remember($cacheKey, 3600, function () use ($selectedId) {
            $tree           = [];
            $rootCategories = $this->repository->getVisibleRootCategories();

            foreach ($rootCategories as $category) {
                $tree[] = $this->buildTreeNode($category, $selectedId);
            }

            return $tree;
        });
    }

    /**
     * Constrói recursivamente cada nó da árvore.
     */
    private function buildTreeNode(Category $category, int $selectedId): array
    {
        $children       = $category->getChild();
        $nodes          = [];
        $isChildSelected = false;

        foreach ($children as $child) {
            $childNode = $this->buildTreeNode($child, $selectedId);
            $nodes[]   = $childNode;

            if ($childNode['state']['selected'] || $childNode['state']['expanded']) {
                $isChildSelected = true;
            }
        }

        $isActive = $category->category_id == $selectedId;

        return [
            'id'      => $category->category_id,
            'text'    => $category->getName(),
            'url'     => url('solution/category/' . $category->category_id),
            'nodes'   => !empty($nodes) ? $nodes : null,
            'is_root' => $category->parent_id == 0,
            'state'   => [
                'selected' => $isActive,
                'expanded' => $isActive || $isChildSelected,
            ],
        ];
    }

    /**
     * Retorna uma lista "achatada" (flattened) para selects e breadcrumbs.
     * Ex: "Hardware > Impressoras > Laser"
     */
    public function getFlattenedHierarchy(): array
    {
        return Cache::remember('kb_hierarchy_flat', 3600, function () {
            $data  = [];
            $roots = $this->repository->getVisibleRootCategories();

            foreach ($roots as $root) {
                $this->flatten($root, $root->getName(), $data);
            }

            return $data;
        });
    }

    private function flatten(Category $category, string $title, array &$data): void
    {
        $data[] = [
            'id'    => $category->category_id,
            'name'  => $title,
            'order' => $category->sort_order,
        ];

        foreach ($category->getChild() as $child) {
            $this->flatten($child, $title . ' > ' . $child->getName(), $data);
        }
    }

    /**
     * Busca soluções relacionadas baseadas em tags ou conteúdo.
     */
    public function getRelatedSolutions(Solution $solution, int $limit = 5): Collection
    {
        return $this->repository->getRelatedSolutions($solution, $limit);
    }
}
