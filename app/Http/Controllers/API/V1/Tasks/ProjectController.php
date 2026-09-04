<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\ProjectResource;
use App\Services\ProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ProjectController extends Controller
{
    public function __construct(
        protected ProjectService $projectService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/projects',
        summary: 'Listar projetos',
        description: 'Retorna a lista de projetos ativos. Use already=true para saber se você participa de cada projeto.',
        operationId: 'listTaskProjects',
        tags: ['Projetos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'already',
                in: 'query',
                description: 'Verificar participação do usuário logado',
                required: false,
                schema: new OA\Schema(type: 'boolean', example: true)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskProjectResource'))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $projects = $this->projectService->getActiveProjects(
            $request->boolean('already'),
            Auth::id()
        );

        return ProjectResource::collection($projects);
    }

    #[OA\Post(
        path: '/api/v1/tasks/projects/toggle',
        summary: 'Alternar participação no projeto',
        description: 'Entra no projeto se não estiver participando, ou sai se já estiver.',
        operationId: 'toggleProjectParticipation',
        tags: ['Projetos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['project_id'],
                properties: [
                    new OA\Property(property: 'project_id', type: 'integer', example: 5)
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Estado de participação atualizado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'already', type: 'boolean', example: true)
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação (projeto inexistente)')
        ]
    )]
    public function toggleParticipation(Request $request): JsonResponse
    {
        $request->validate(['project_id' => 'required|exists:projects,id']);
        
        $already = $this->projectService->toggleParticipation(
            $request->project_id,
            Auth::id()
        );

        return response()->json(['already' => $already]);
    }
}