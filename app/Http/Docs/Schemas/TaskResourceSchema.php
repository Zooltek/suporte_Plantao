<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskResource',
    title: 'Task Resource',
    description: 'Representação completa de uma tarefa',
    type: 'object'
)]
class TaskResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 42)]
    public int $id;

    #[OA\Property(property: 'title', type: 'string', example: 'Título da Tarefa')]
    public string $title;

    #[OA\Property(property: 'content', type: 'string', example: 'Descrição detalhada')]
    public string $content;

    #[OA\Property(property: 'status', type: 'string', example: 'pen')]
    public string $status;

    #[OA\Property(property: 'started_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $started_at;

    #[OA\Property(property: 'completed_at', type: 'string', format: 'date-time', nullable: true)]
    public ?string $completed_at;

    #[OA\Property(property: 'customer_software', type: 'string', example: 'Amura ERP')]
    public string $customer_software;

    #[OA\Property(property: 'subtasksCount', type: 'integer', example: 3)]
    public int $subtasksCount;

    #[OA\Property(
        property: 'author',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'Admin')
        ]
    )]
    public object $author;

    #[OA\Property(
        property: 'project',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 5),
            new OA\Property(property: 'name', type: 'string', example: 'Projeto X')
        ]
    )]
    public object $project;

    #[OA\Property(
        property: 'labels',
        type: 'array',
        items: new OA\Items(ref: '#/components/schemas/TaskLabelResource')
    )]
    public array $labels;
}