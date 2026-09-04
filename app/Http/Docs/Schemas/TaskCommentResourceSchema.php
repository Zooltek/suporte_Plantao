<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskCommentResource',
    title: 'Task Comment Resource',
    type: 'object'
)]
class TaskCommentResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 501)]
    public int $id;

    #[OA\Property(property: 'content', type: 'string', example: 'Trabalho iniciado conforme solicitado.')]
    public string $content;

    #[OA\Property(property: 'task_id', type: 'integer', example: 1234)]
    public int $task_id;

    #[OA\Property(property: 'user', type: 'object', properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'João Silva')
    ])]
    public object $user;

    #[OA\Property(property: 'created_at', type: 'string', format: 'date-time')]
    public string $created_at;
}