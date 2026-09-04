<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'ScheduleResource',
    title: 'Schedule Resource',
    description: 'Representação de um agendamento no calendário',
    type: 'object'
)]
class ScheduleResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 12)]
    public int $id;

    #[OA\Property(property: 'title', type: 'string', example: 'Reunião de Alinhamento')]
    public string $title;

    #[OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-02-20 09:00:00')]
    public string $start;

    #[OA\Property(property: 'end', type: 'string', format: 'date-time', example: '2026-02-20 10:30:00')]
    public string $end;

    #[OA\Property(property: 'status', type: 'string', enum: ['pen', 'fin'], example: 'pen')]
    public string $status;

    #[OA\Property(property: 'customer_name', type: 'string', example: 'Amura Tecnologia')]
    public string $customer_name;
}