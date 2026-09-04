<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Tasks\Changelog;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\V1\Tasks\Changelog\VersionStoreRequest;
use App\Http\Resources\Tasks\Changelog\VersionResource;
use App\Services\VersionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Exception;

class VersionController extends Controller
{
    public function __construct(
        protected VersionService $versionService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/changelog/versions',
        summary: 'Listar versões de changelog',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Sucesso', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskVersionResource'))
            ]))
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $versions = $this->versionService->getVersions(
            $request->project_id ? (int) $request->project_id : null
        );

        return VersionResource::collection($versions);
    }

    #[OA\Post(
        path: '/api/v1/tasks/changelog/versions',
        summary: 'Criar nova versão e fechar changelogs',
        tags: ['Changelog'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TaskVersionStoreRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Criado', content: new OA\JsonContent(ref: '#/components/schemas/TaskVersionResource')),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(VersionStoreRequest $request): JsonResponse
    {
        try {
            $version = $this->versionService->createVersion($request->validated(), Auth::id());

            return (new VersionResource($version))
                ->response()
                ->setStatusCode(201);
                
        } catch (Exception $e) {
            // Tenta decodificar o JSON enviado pelo Service. 
            // Se não for um JSON válido, retorna a string da exceção como fallback.
            $payload = json_decode($e->getMessage(), true) ?? ['message' => $e->getMessage()];
            $statusCode = $e->getCode() ?: 500;
            
            return response()->json($payload, $statusCode);
        }
    }
}