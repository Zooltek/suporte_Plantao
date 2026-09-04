<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskChangelog',
    title: 'Task Changelog Resource',
    type: 'object'
)]
class TaskChangelogSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 101)]
    public int $id;

    #[OA\Property(property: 'content', type: 'string', example: 'Ajuste no layout do dashboard')]
    public string $content;

    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'sort_order', type: 'number', format: 'float', example: 10000.5)]
    public float $sort_order;

    #[OA\Property(property: 'user', type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Desenvolvedor TI')
    ])]
    public object $user;
}