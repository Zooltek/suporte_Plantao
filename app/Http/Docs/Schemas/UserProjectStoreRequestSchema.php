<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'UserProjectStoreRequest',
    title: 'User Project Store Request',
    required: ['project_id'],
    type: 'object'
)]
class UserProjectStoreRequestSchema
{
    #[OA\Property(property: 'project_id', type: 'integer', example: 5)]
    public int $project_id;

    #[OA\Property(property: 'color', type: 'string', example: '#FF5733', nullable: true)]
    public ?string $color;
}