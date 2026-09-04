<?php

namespace App\Http\Docs\Schemas\Admin;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AdminScheduleResource',
    title: 'Admin Schedule Resource',
    type: 'object'
)]
class ScheduleResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'title', type: 'string', example: 'Atendimento Cliente XYZ')]
    public string $title;

    #[OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-02-20T09:00:00Z')]
    public string $start;

    #[OA\Property(property: 'end', type: 'string', format: 'date-time', example: '2026-02-20T11:00:00Z')]
    public string $end;

    #[OA\Property(property: 'agent_id', type: 'integer', example: 5)]
    public int $agent_id;

    #[OA\Property(property: 'customer_id', type: 'integer', nullable: true, example: 10)]
    public ?int $customer_id;
}