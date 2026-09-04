<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskProjectResource',
    title: 'Task Project Resource',
    type: 'object'
)]
class TaskProjectResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 5)]
    public int $id;

    #[OA\Property(property: 'name', type: 'string', example: 'Projeto Amura')]
    public string $name;

    #[OA\Property(property: 'status', type: 'integer', example: 1)]
    public int $status;

    #[OA\Property(
        property: 'already', 
        type: 'boolean', 
        description: 'Indica se o usuário logado participa do projeto (retornado quando already=true na query)',
        example: true
    )]
    public bool $already;
}