<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskVersionResource',
    title: 'Task Version Resource',
    type: 'object'
)]
class TaskVersionResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'name', type: 'string', example: 'v1.2.0')]
    public string $name;

    #[OA\Property(property: 'reference_date', type: 'string', format: 'date-time', example: '2026-02-17 14:00:00')]
    public string $reference_date;

    #[OA\Property(
        property: 'changelogs',
        type: 'array',
        items: new OA\Items(
            properties: [
                new OA\Property(property: 'id', type: 'integer', example: 101),
                new OA\Property(property: 'description', type: 'string', example: 'Correção no módulo de login'),
                new OA\Property(property: 'sort_order', type: 'integer', example: 1)
            ]
        )
    )]
    public array $changelogs;
}