<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskVersionStoreRequest',
    title: 'Task Version Store Request',
    required: ['project_id', 'name'],
    type: 'object'
)]
class TaskVersionStoreRequestSchema
{
    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'name', type: 'string', example: 'v1.2.0')]
    public string $name;

    #[OA\Property(property: 'reference_date', type: 'string', format: 'date', example: '2026-02-17')]
    public string $reference_date;

    #[OA\Property(property: 'time', type: 'string', example: '14:30', description: 'Opcional. Se não enviado, usa a hora atual.')]
    public string $time;
}