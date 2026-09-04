<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ElementTypeRepositoryInterface;
use App\Models\Crm\Feedback\ElementType;
use Illuminate\Database\Eloquent\Collection;

class ElementTypeRepository implements ElementTypeRepositoryInterface
{
    public function getAll(): Collection
    {
        return ElementType::all();
    }

    public function find(int $id): ElementType
    {
        return ElementType::findOrFail($id);
    }

    public function create(array $data): ElementType
    {
        return ElementType::create($data);
    }

    public function update(ElementType $elementType, array $data): ElementType
    {
        $elementType->update($data);

        return $elementType->fresh();
    }

    public function delete(ElementType $elementType): void
    {
        $elementType->delete();
    }
}
