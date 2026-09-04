<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\HelpdeskCategoryRepositoryInterface;
use App\Helpers\TicketitHelper;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    protected const CACHE_KEY = 'helpdesk_category_hierarchy';

    public function __construct(
        private readonly HelpdeskCategoryRepositoryInterface $repository,
    ) {}

    /**
     * Retorna a hierarquia completa de categorias processada.
     */
    public function getFullHierarchy(int $ticketCategoryId): array
    {
        $key = self::CACHE_KEY . "_setor_{$ticketCategoryId}";

        return Cache::remember($key, 86400, function () use ($ticketCategoryId) {
            $data = [];
            $i    = 0;
            $rootCategories = $this->repository->getRootByTicketCategory($ticketCategoryId);

            foreach ($rootCategories as $category) {
                $title = $category->getName();
                $this->mapNode($category, $title, $data, $i);
            }

            return $data;
        });
    }

    /**
     * Mapeia o nó e seus filhos recursivamente.
     */
    private function mapNode($category, string $title, array &$data, int &$i): void
    {
        $data[$i] = [
            'name'  => $title,
            'order' => $category->sort_order,
            'id'    => $category->category_id,
        ];
        $i++;

        foreach ($category->getChild() as $child) {
            $newTitle = $title . ' > ' . $child->getName();
            $this->mapNode($child, $newTitle, $data, $i);
        }
    }

    /**
     * Salva ou atualiza uma categoria e sua descrição.
     */
    public function storeOrUpdate(array $data, ?int $id = null): Category
    {
        return $this->repository->storeOrUpdate($data, $id);
    }

    /**
     * Busca filhos para AJAX ordenados pelo Helper.
     */
    public function getChildrenForAjax(int $parentId): array
    {
        $categories = $this->repository->getChildrenWithDescription($parentId);

        $data = $categories->map(fn ($cat) => [
            'id'        => $cat->category_id,
            'name'      => $cat->description->name,
            'permalink' => $cat->description->permalink ?? '',
        ]);

        return TicketitHelper::sortData($data, 'name', 'asc')->values()->toArray();
    }
}
