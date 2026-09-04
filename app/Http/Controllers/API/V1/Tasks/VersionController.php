<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\VersionResource;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class VersionController extends Controller
{
    #[OA\Get(
        path: '/api/v1/tasks/version',
        summary: 'Obter versão da API',
        description: 'Retorna a versão atual do sistema, o ambiente configurado e o timestamp do servidor.',
        operationId: 'getAppVersion',
        tags: ['Sistema'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AppVersionResource')
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(): VersionResource
    {
        $data = (object) [
            'version' => '3.0.0',
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String()
        ];
        return new VersionResource($data);
    }
}