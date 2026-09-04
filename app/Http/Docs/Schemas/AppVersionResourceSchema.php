<?php

namespace App\Http\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AppVersionResource',
    title: 'App Version Resource',
    description: 'Informações sobre a versão e o ambiente da API',
    type: 'object'
)]
class AppVersionResourceSchema
{
    #[OA\Property(property: 'version', type: 'string', example: '3.0.0')]
    public string $version;

    #[OA\Property(property: 'environment', type: 'string', example: 'production')]
    public string $environment;

    #[OA\Property(property: 'timestamp', type: 'string', format: 'date-time', example: '2026-02-17T14:55:00-03:00')]
    public string $timestamp;
}