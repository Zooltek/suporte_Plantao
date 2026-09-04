<?php

namespace App\Services\API\V1\Crm;

use App\Contracts\Repositories\CrmElementRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CrmElementService
{
    public function __construct(
        private readonly CrmElementRepositoryInterface $repository
    ) {}

    public function listByForm(int $formId): Collection
    {
        return $this->repository->listByForm($formId);
    }
}
