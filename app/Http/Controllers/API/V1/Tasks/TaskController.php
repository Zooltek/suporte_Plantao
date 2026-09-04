<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\Tasks\{TaskStoreRequest, TaskUpdateRequest, TaskUploadRequest};
use App\Http\Resources\API\V1\Tasks\TaskResource;
use App\Services\API\V1\Tasks\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;
use Exception;

class TaskController extends Controller
{
    public function __construct(
        protected TaskService $taskService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks',
        summary: 'Listar tasks',
        description: 'Retorna uma lista de tasks com filtros. Suporta Scout, filtros por status, agente, cliente, etc.',
        operationId: 'getTasksV1',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'query', in: 'query', description: 'Busca textual', schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'agent_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'customer_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'project_id', in: 'query', schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'bin', in: 'query', schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'limite', in: 'query', schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskResource'))])
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection|TaskResource
    {
        $result = $this->taskService->getTasks($request->all(), $request->id);

        if ($request->id) {
            return $result ? new TaskResource($result) : TaskResource::collection([]);
        }

        return TaskResource::collection($result);
    }

    #[OA\Post(
        path: '/api/v1/tasks',
        summary: 'Criar nova task',
        operationId: 'storeTaskV1',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TaskStoreRequest')),
        responses: [
            new OA\Response(response: 201, description: 'Criado', content: new OA\JsonContent(ref: '#/components/schemas/TaskResource'))
        ]
    )]
    public function store(TaskStoreRequest $request): JsonResponse
    {
        $task = $this->taskService->createTask($request->validated());

        return (new TaskResource($task))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Patch(
        path: '/api/v1/tasks/{id}/status',
        summary: 'Atualizar status da task',
        operationId: 'updateTaskStatusV1',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/TaskStatusUpdateRequest')),
        responses: [
            new OA\Response(response: 200, description: 'Sucesso'),
            new OA\Response(response: 403, description: 'Não autorizado')
        ]
    )]
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status'      => ['required', 'string', 'in:pen,pro,don,tdo,can,bin'],
            'description' => ['nullable', 'string'],
        ]);

        try {
            $this->taskService->updateTaskStatus($id, $validated);
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            $status = $e->getCode() ?: 500;
            return response()->json(['error' => $e->getMessage()], $status === 403 ? 403 : $status);
        }
    }

    #[OA\Delete(
        path: '/api/v1/tasks/{id}',
        summary: 'Excluir/Cancelar task',
        operationId: 'deleteTaskV1',
        tags: ['Tasks'],
        security: [['sanctum' => []]],
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Sucesso')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->taskService->destroyTask($id);
            return response()->json(['status' => true]);
        } catch (Exception $e) {
            $status = $e->getCode() ?: 500;
            return response()->json(['error' => $e->getMessage()], $status === 403 ? 403 : $status);
        }
    }
}