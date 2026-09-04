<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CrmElementRepositoryInterface;
use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Eloquent\Collection;

class CrmElementRepository implements CrmElementRepositoryInterface
{
    /**
     * Retorna os ElementTypes de um formulário CRM, ordenados por sort_order.
     */
    public function listByForm(int $formId): Collection
    {
        return ElementType::query()
            ->where('form_id', $formId)
            ->orderBy('sort_order', 'asc')
            ->get();
    }
}
