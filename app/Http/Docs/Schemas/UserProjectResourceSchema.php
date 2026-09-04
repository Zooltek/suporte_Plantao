<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserProjectResource',
    title: 'User Project Resource',
    type: 'object'
)]
class UserProjectResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'user_id', type: 'integer', example: 10)]
    public int $user_id;

    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'color', type: 'string', example: '#3490dc', nullable: true)]
    public ?string $color;

    #[OA\Property(property: 'project', ref: '#/components/schemas/TaskProjectResource')]
    public object $project;
}