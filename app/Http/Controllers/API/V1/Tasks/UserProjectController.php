<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\UserProjectResource;
use App\Services\UserProjectService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Exception;

class UserProjectController extends Controller
{
    public function __construct(
        protected UserProjectService $userProjectService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/user-projects',
        summary: 'Listar projetos vinculados ao usuário',
        description: 'Retorna os projetos que o usuário logado participa, ordenados pelo nome do projeto.',
        operationId: 'listUserProjects',
        tags: ['Projetos - Vínculos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/UserProjectResource'))
                    ]
                )
            )
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        $userProjects = $this->userProjectService->getUserProjects(Auth::id());

        return UserProjectResource::collection($userProjects);
    }

    #[OA\Post(
        path: '/api/v1/tasks/user-projects',
        summary: 'Vincular usuário a um projeto',
        operationId: 'storeUserProject',
        tags: ['Projetos - Vínculos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UserProjectStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Vínculo criado',
                content: new OA\JsonContent(ref: '#/components/schemas/UserProjectResource')
            ),
            new OA\Response(response: 422, description: 'Usuário já vinculado ou projeto inexistente')
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|exists:projects,id',
            'color' => 'nullable|string'
        ]);

        try {
            $userProject = $this->userProjectService->assignProject(
                Auth::id(),
                $request->project_id,
                $request->color
            );

            return (new UserProjectResource($userProject))
                ->response()
                ->setStatusCode(201);
        } catch (Exception $e) {
            return response()->json([
                'status' => false, 
                'message' => $e->getMessage()
            ], $e->getCode() ?: 422);
        }
    }

    #[OA\Delete(
        path: '/api/v1/tasks/user-projects/{id}',
        summary: 'Remover vínculo por ID',
        operationId: 'deleteUserProject',
        tags: ['Projetos - Vínculos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vínculo removido')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->userProjectService->deleteById($id, Auth::id());

        return response()->json(['status' => true]);
    }

    #[OA\Delete(
        path: '/api/v1/tasks/user-projects/dissociate/{project_id}',
        summary: 'Remover vínculo por ID de projeto',
        operationId: 'dissociateUserProject',
        tags: ['Projetos - Vínculos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'project_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Vínculo removido'),
            new OA\Response(response: 422, description: 'Vínculo não encontrado')
        ]
    )]
    public function dissociate(int $project_id): JsonResponse
    {
        try {
            $this->userProjectService->dissociateProject($project_id, Auth::id());
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            return response()->json([
                'status' => false, 
                'message' => $e->getMessage()
            ], $e->getCode() ?: 422);
        }
    }
}