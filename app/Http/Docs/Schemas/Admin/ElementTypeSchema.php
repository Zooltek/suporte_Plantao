<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ElementType',
    title: 'Element Type Resource',
    description: 'Representação de um tipo de elemento CRM',
    type: 'object'
)]
class ElementTypeSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'Sugestão')]
    public string $name;

    #[OA\Property(property: 'active', type: 'boolean', example: true)]
    public bool $active;
}