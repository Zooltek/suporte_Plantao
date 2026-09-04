<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface CrmElementRepositoryInterface
{
    /**
     * Retorna os ElementTypes de um formulário CRM, ordenados por sort_order.
     */
    public function listByForm(int $formId): Collection;
}
