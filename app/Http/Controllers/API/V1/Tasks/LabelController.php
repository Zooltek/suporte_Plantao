<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Http\Resources\API\V1\Tasks\LabelResource;
use App\Services\API\V1\Tasks\LabelService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;
use Exception;

class LabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    #[OA\Get(
        path: '/api/v1/tasks/labels',
        summary: 'Listar etiquetas',
        description: 'Retorna etiquetas do sistema. Se mode=module, retorna etiquetas do departamento. Caso contrário, retorna etiquetas pessoais.',
        operationId: 'listTaskLabels',
        tags: ['Labels'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'mode', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'module')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/TaskLabelResource'))
                    ]
                )
            )
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $labels = $this->labelService->getLabels(
            $request->mode,
            $request->user_id ? (int) $request->user_id : null,
            Auth::id()
        );

        return LabelResource::collection($labels);
    }

    #[OA\Post(
        path: '/api/v1/tasks/labels',
        summary: 'Criar etiqueta pessoal',
        operationId: 'storeTaskLabel',
        tags: ['Labels'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/TaskLabelStoreRequest')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Criado', content: new OA\JsonContent(ref: '#/components/schemas/TaskLabelResource'))
        ]
    )]
    public function store(Request $request): JsonResponse
    {
        // Assumindo que a validação de 'name' é feita pelo TaskLabelStoreRequest ou similar
        $label = $this->labelService->createPersonalLabel(
            $request->name,
            Auth::id()
        );

        return (new LabelResource($label))->response()->setStatusCode(201);
    }

    #[OA\Post(
        path: '/api/v1/tasks/labels/assign/{task_id}/{label_id}',
        summary: 'Vincular etiqueta a uma task',
        operationId: 'assignTaskLabel',
        tags: ['Labels'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'task_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'label_id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 201, description: 'Vinculado com sucesso'),
            new OA\Response(
                response: 422,
                description: 'Erro de permissão ou vínculo duplicado',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'string', example: 'already_assigned')])
            )
        ]
    )]
    public function assign(int $task_id, int $label_id): JsonResponse
    {
        try {
            $taskLabel = $this->labelService->assignLabelToTask($task_id, $label_id);
            
            return (new LabelResource($taskLabel))->response()->setStatusCode(201);
        } catch (Exception $e) {
            // Retorna o status customizado (unauthorized ou already_assigned) que a Service lançou
            return response()->json(['status' => $e->getMessage()], 422);
        }
    }

    #[OA\Delete(
        path: '/api/v1/tasks/labels/{id}',
        summary: 'Remover etiqueta',
        description: 'Desativa a etiqueta e remove todos os vínculos dela com tasks.',
        operationId: 'deleteTaskLabel',
        tags: ['Labels'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 200, description: 'Removido com sucesso')
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $this->labelService->destroyLabel($id);

        return response()->json(['status' => true]);
    }
}