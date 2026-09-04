<?php

namespace App\Http\Controllers\Admin\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\API\V1\DepartmentRequest;
use App\Http\Resources\Admin\API\V1\DepartmentResource;
use App\Services\Admin\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    #[OA\Get(
        path: '/admin/api/v1/departments',
        summary: 'Listar todos os departamentos',
        operationId: 'getDepartmentsAdmin',
        tags: ['Admin - Departamentos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/DepartmentResource')
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Não autorizado'),
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return DepartmentResource::collection(
            $this->departmentService->getAllDepartments()
        );
    }

    #[OA\Post(
        path: '/admin/api/v1/departments',
        summary: 'Criar novo departamento',
        operationId: 'storeDepartmentAdmin',
        tags: ['Admin - Departamentos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DepartmentRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Criado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message',    type: 'string', example: 'Departamento criado com sucesso!'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DepartmentResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(DepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->createDepartment($request->validated());

        return (new DepartmentResource($department))
            ->additional(['message' => 'Departamento criado com sucesso!'])
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/admin/api/v1/departments/{id}',
        summary: 'Obter detalhes de um departamento',
        operationId: 'showDepartmentAdmin',
        tags: ['Admin - Departamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/DepartmentResource')
            ),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
    public function show(int $id): DepartmentResource
    {
        return new DepartmentResource(
            $this->departmentService->findDepartment($id)
        );
    }

    #[OA\Put(
        path: '/admin/api/v1/departments/{id}',
        summary: 'Atualizar departamento',
        operationId: 'updateDepartmentAdmin',
        tags: ['Admin - Departamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/DepartmentRequest')
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Atualizado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Departamento atualizado com sucesso!'),
                        new OA\Property(property: 'data', ref: '#/components/schemas/DepartmentResource'),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
    public function update(DepartmentRequest $request, int $id): JsonResponse
    {
        $department = $this->departmentService->findDepartment($id);
        $updated    = $this->departmentService->updateDepartment($department, $request->validated());

        return (new DepartmentResource($updated))
            ->additional(['message' => 'Departamento atualizado com sucesso!'])
            ->response();
    }

    #[OA\Delete(
        path: '/admin/api/v1/departments/{id}',
        summary: 'Excluir departamento',
        operationId: 'destroyDepartmentAdmin',
        tags: ['Admin - Departamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Excluído com sucesso'),
            new OA\Response(response: 422, description: 'Erro de negócio (ex: departamento com vínculos)'),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $department = $this->departmentService->findDepartment($id);

        try {
            $this->departmentService->deleteDepartment($department);
            return response()->json(null, 204);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }
}
