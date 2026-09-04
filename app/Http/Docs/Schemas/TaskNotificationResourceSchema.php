<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskNotificationResource',
    title: 'Task Notification Resource',
    type: 'object'
)]
class TaskNotificationResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'title', type: 'string', example: 'Nova tarefa atribuída')]
    public string $title;

    #[OA\Property(property: 'seen', type: 'integer', example: 0)]
    public int $seen;

    #[OA\Property(property: 'icon', type: 'string', example: 'task-new.png')]
    public string $icon;

    #[OA\Property(property: 'timestamp', type: 'string', example: 'há 5 minutos')]
    public string $timestamp;

    #[OA\Property(property: 'url', type: 'string', example: 'ae')]
    public string $url;
}