<?php

declare(strict_types=1);

namespace App\Services\Admin\Crm;

use App\Contracts\Repositories\ElementTypeRepositoryInterface;
use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Eloquent\Collection;

class ElementTypeService
{
    public function __construct(
        private readonly ElementTypeRepositoryInterface $repository,
    ) {}

    public function getAll(): Collection
    {
        return $this->repository->getAll();
    }

    public function find(int $id): ElementType
    {
        return $this->repository->find($id);
    }

    public function create(array $data): ElementType
    {
        return $this->repository->create($data);
    }

    public function update(ElementType $elementType, array $data): ElementType
    {
        return $this->repository->update($elementType, $data);
    }

    public function delete(ElementType $elementType): void
    {
        $this->repository->delete($elementType);
    }
}
