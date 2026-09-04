<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ElementTypeStoreRequest',
    title: 'Element Type Store Request',
    required: ['name'],
    type: 'object'
)]
class ElementTypeStoreRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', minLength: 3, maxLength: 255, example: 'Crítica')]
    public string $name;

    #[OA\Property(property: 'active', type: 'boolean', default: true, example: true)]
    public bool $active;
}