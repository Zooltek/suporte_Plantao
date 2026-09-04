<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskLabelStoreRequest',
    title: 'Task Label Store Request',
    required: ['name'],
    type: 'object'
)]
class TaskLabelStoreRequestSchema
{
    #[OA\Property(property: 'name', type: 'string', example: 'Minha Etiqueta')]
    public string $name;
}