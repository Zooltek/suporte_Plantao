<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskStoreRequest',
    title: 'Task Store Request',
    required: ['title', 'content', 'project_id'],
    type: 'object'
)]
class TaskStoreRequestSchema
{
    #[OA\Property(property: 'title', type: 'string', example: 'Corrigir erro de autenticação')]
    public string $title;

    #[OA\Property(property: 'content', type: 'string', example: 'Descrição detalhada do problema técnico')]
    public string $content;

    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'user_id', type: 'integer', nullable: true, description: 'ID do agente responsável')]
    public ?int $user_id;

    #[OA\Property(property: 'request_at', type: 'string', example: '20/02/2026', description: 'Formato d/m/Y')]
    public string $request_at;

    #[OA\Property(property: 'uploads', type: 'string', example: '1,2,3', description: 'IDs de anexos separados por vírgula')]
    public string $uploads;
}