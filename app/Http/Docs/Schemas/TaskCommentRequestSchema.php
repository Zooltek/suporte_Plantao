<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskCommentRequest',
    title: 'Task Comment Request',
    required: ['content', 'task_id'],
    type: 'object'
)]
class TaskCommentRequestSchema
{
    #[OA\Property(property: 'content', type: 'string', example: 'Este é um comentário de teste.')]
    public string $content;

    #[OA\Property(property: 'task_id', type: 'integer', example: 1234)]
    public int $task_id;
}