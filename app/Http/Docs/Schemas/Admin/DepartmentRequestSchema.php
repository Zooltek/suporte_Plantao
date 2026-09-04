<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'DepartmentRequest',
    title: 'Department Request',
    required: ['name'],
    type: 'object'
)]
class DepartmentRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', maxLength: 255, example: 'Financeiro')]
    public string $name;

    #[OA\Property(property: 'description', type: 'string', maxLength: 500, nullable: true, example: 'Gestão de contas')]
    public string $description;
}