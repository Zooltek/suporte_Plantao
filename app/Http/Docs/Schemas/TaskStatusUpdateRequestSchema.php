<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'TaskStatusUpdateRequest',
    title: 'Task Status Update Request',
    required: ['status'],
    type: 'object'
)]
class TaskStatusUpdateRequestSchema
{
    #[OA\Property(property: 'status', type: 'string', enum: ['pen', 'pro', 'don', 'tdo', 'can', 'rej'], example: 'pro')]
    public string $status;

    #[OA\Property(property: 'description', type: 'string', nullable: true, description: 'Observação sobre a mudança')]
    public ?string $description;
}