<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskChangelogRequest',
    title: 'Task Changelog Request',
    required: ['content', 'project_id'],
    type: 'object'
)]
class TaskChangelogRequestSchema
{
    #[OA\Property(property: 'content', type: 'string', example: 'Nova funcionalidade de relatórios')]
    public string $content;

    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'task_id', type: 'integer', nullable: true, example: 1234)]
    public ?int $task_id;

    #[OA\Property(property: 'color', type: 'string', nullable: true, example: '#FF0000')]
    public ?string $color;

    #[OA\Property(property: 'title', type: 'boolean', example: false)]
    public bool $title;
}