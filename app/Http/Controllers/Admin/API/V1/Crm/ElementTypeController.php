<?php

namespace App\Http\Controllers\Admin\API\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\API\V1\Crm\ElementTypeStoreRequest;
use App\Http\Resources\Admin\API\V1\Crm\ElementTypeResource;
use App\Models\Crm\Feedback\ElementType;
use App\Services\Admin\Crm\ElementTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ElementTypeController extends Controller
{
    private const SCHEMA_REF    = '#/components/schemas/ElementType';
    private const RESOURCE_PATH = '/api/v1/admin/crm/element-types/{id}';
    private const MSG_NOT_FOUND = 'Não encontrado';

    public function __construct(
        protected ElementTypeService $elementTypeService
    ) {}

    #[OA\Get(
        path: '/api/v1/admin/crm/element-types',
        summary: 'Listar todos os tipos de elemento do CRM',
        description: 'Retorna uma lista de todos os tipos de elemento do CRM.',
        operationId: 'listElementTypes',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de tipos de elemento retornada com sucesso',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: self::SCHEMA_REF)
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Não autorizado')
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return ElementTypeResource::collection(
            $this->elementTypeService->getAll()
        );
    }

    #[OA\Post(
        path: '/api/v1/admin/crm/element-types',
        summary: 'Criar tipo de elemento do CRM',
        description: 'Cria um novo tipo de elemento no CRM. Requer permissão de administrador.',
        operationId: 'createElementType',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Dados do tipo de elemento',
            content: new OA\JsonContent(ref: '#/components/schemas/ElementTypeStoreRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Tipo de elemento criado com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: self::SCHEMA_REF)
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação')
        ]
    )]
    public function store(ElementTypeStoreRequest $request): JsonResponse
    {
        $elementType = $this->elementTypeService->create($request->validated());

        return (new ElementTypeResource($elementType))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: self::RESOURCE_PATH,
        summary: 'Exibir tipo de elemento do CRM',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: self::SCHEMA_REF)
                    ]
                )
            ),
            new OA\Response(response: 404, description: self::MSG_NOT_FOUND)
        ]
    )]
    public function show(ElementType $elementType): ElementTypeResource
    {
        return new ElementTypeResource($elementType);
    }

    #[OA\Put(
        path: self::RESOURCE_PATH,
        summary: 'Atualizar tipo de elemento do CRM',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ElementTypeStoreRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizado com sucesso'),
            new OA\Response(response: 422, description: 'Erro de validação'),
            new OA\Response(response: 404, description: self::MSG_NOT_FOUND)
        ]
    )]
    public function update(ElementTypeStoreRequest $request, ElementType $elementType): ElementTypeResource
    {
        $updated = $this->elementTypeService->update($elementType, $request->validated());

        return new ElementTypeResource($updated);
    }

    #[OA\Delete(
        path: self::RESOURCE_PATH,
        summary: 'Excluir tipo de elemento do CRM',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))
        ],
        responses: [
            new OA\Response(response: 204, description: 'Excluído com sucesso'),
            new OA\Response(response: 404, description: self::MSG_NOT_FOUND)
        ]
    )]
    public function destroy(ElementType $elementType): JsonResponse
    {
        $this->elementTypeService->delete($elementType);

        return response()->json(null, 204);
    }
}
