<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ScheduleRequest',
    title: 'Schedule Request',
    required: ['title', 'start', 'end'],
    type: 'object'
)]
class ScheduleRequestSchema
{
    #[OA\Property(property: 'title', type: 'string', example: 'Atendimento Cliente XYZ')]
    public string $title;

    #[OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-02-20 09:00:00')]
    public string $start;

    #[OA\Property(property: 'end', type: 'string', format: 'date-time', example: '2026-02-20 11:00:00')]
    public string $end;

    #[OA\Property(property: 'agent_id', type: 'integer', example: 5)]
    public int $agent_id;

    #[OA\Property(property: 'customer_id', type: 'integer', nullable: true, example: 10)]
    public ?int $customer_id;
}