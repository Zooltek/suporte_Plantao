<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'CategoryRequest',
    title: 'Category Request',
    required: ['name'],
    type: 'object'
) ]
class CategoryRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', example: 'Novo Nome da Categoria')]
    public string $name;

    #[OA\Property(property: 'description', type: 'string', example: 'Texto descritivo')]
    public string $description;
}