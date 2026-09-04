<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepartmentResource',
    title: 'Department Resource',
    type: 'object'
)]
class DepartmentResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'TI - Suporte')]
    public string $name;

    #[OA\Property(property: 'description', type: 'string', example: 'Responsável pela infraestrutura e suporte')]
    public string $description;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    public string $created_at;
}