<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskLabelResource',
    title: 'Task Label Resource',
    type: 'object'
)]
class TaskLabelResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'Urgente')]
    public string $name;

    #[OA\Property(property: 'color', type: 'string', nullable: true, example: '#FF0000')]
    public ?string $color;

    #[OA\Property(
        property: 'childs',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/TaskLabelResource'),
        description: 'Sub-etiquetas (apenas no modo module)'
    )]
    public array $childs;
}