<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RecordResource',
    title: 'Schedule Record Resource',
    type: 'object'
)]
class RecordResourceSchema
{
    #[OA\Property(property: 'id', type: 'integer', example: 1)]
    public int $id;

    #[OA\Property(property: 'start', type: 'string', format: 'date-time', example: '2026-02-20T09:00:00Z')]
    public string $start;

    #[OA\Property(property: 'end', type: 'string', format: 'date-time', example: '2026-02-20T11:00:00Z')]
    public string $end;

    #[OA\Property(property: 'agent', ref: '#/components/schemas/UserSchema')]
    public object $agent;

    #[OA\Property(property: 'customer', ref: '#/components/schemas/CategoryResource')]
    public object $customer;

    #[OA\Property(
        property: 'module',
        type: 'object',
        properties: [
            new OA\Property(property: 'id', type: 'integer', example: 1),
            new OA\Property(property: 'name', type: 'string', example: 'CRM')
        ]
    )]
    public object $module;
}