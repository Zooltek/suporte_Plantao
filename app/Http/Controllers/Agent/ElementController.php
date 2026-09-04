<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreElementTypeRequest;
use App\Http\Requests\UpdateElementTypeRequest;
use App\Http\Resources\Agent\ElementResource;
use App\Models\Schedule\ElementType;
use App\Services\Agent\ElementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use OpenApi\Attributes as OA;

class ElementController extends Controller
{
    public function __construct(
        private readonly ElementService $elementService
    ) {}

    #[OA\Get(
        path: '/agent/elements',
        summary: 'Listar todos os elementos agrupados',
        description: 'Retorna uma lista de todos os elementos agrupados pelo nome do grupo. Permite filtrar por módulo.',
        operationId: 'getElementsAgent',
        tags: ['Agent - Elementos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'module_id',
                description: 'ID do módulo para filtrar os elementos',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'module_context',
                description: 'Contexto do module_id: schedule (schedule_record_module.id) ou ticket (company_module_types.id)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['schedule', 'ticket'])
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    type: 'object',
                    description: 'Objeto com chaves dinâmicas (nome do grupo) contendo arrays de ElementResource',
                    additionalProperties: new OA\AdditionalProperties(
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/ElementResource')
                    )
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $context = $request->input('module_context');

        $groupedData = $this->elementService->getGroupedElements(
            $request->filled('module_id') ? (int) $request->input('module_id') : null,
            $context === ElementService::CONTEXT_TICKET
                ? ElementService::CONTEXT_TICKET
                : ElementService::CONTEXT_SCHEDULE
        );

        $formattedData = $groupedData->map(function ($group) {
            return ElementResource::collection($group);
        });

        return response()->json($formattedData);
    }

    #[OA\Post(
        path: '/agent/elements',
        summary: 'Criar um novo elemento',
        description: 'Cria um novo tipo de elemento associado a um módulo e a um grupo.',
        operationId: 'storeElementAgent',
        tags: ['Agent - Elementos'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'module_id', 'group_id'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Reunião Técnica'),
                    new OA\Property(property: 'module_id', type: 'integer', example: 1),
                    new OA\Property(property: 'group_id', type: 'integer', example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Elemento criado com sucesso',
                content: new OA\JsonContent(
                    property: 'data',
                    ref: '#/components/schemas/ElementResource'
                )
            ),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function store(StoreElementTypeRequest $request): ElementResource
    {
        $element = ElementType::create($request->validated());

        return new ElementResource($element);
    }

    #[OA\Get(
        path: '/agent/elements/{id}',
        summary: 'Exibir detalhes de um elemento',
        description: 'Retorna os dados de um elemento específico, incluindo o seu grupo.',
        operationId: 'showElementAgent',
        tags: ['Agent - Elementos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do elemento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    property: 'data',
                    ref: '#/components/schemas/ElementResource'
                )
            ),
            new OA\Response(response: 404, description: 'Elemento não encontrado'),
        ]
    )]
    public function show(ElementType $element): ElementResource
    {
        return new ElementResource($element->load('group'));
    }

    #[OA\Put(
        path: '/agent/elements/{id}',
        summary: 'Atualizar um elemento',
        description: 'Atualiza os dados de um elemento existente.',
        operationId: 'updateElementAgent',
        tags: ['Agent - Elementos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do elemento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'name', type: 'string', example: 'Reunião de Alinhamento Atualizada'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Elemento atualizado com sucesso',
                content: new OA\JsonContent(
                    property: 'data',
                    ref: '#/components/schemas/ElementResource'
                )
            ),
            new OA\Response(response: 404, description: 'Elemento não encontrado'),
            new OA\Response(response: 422, description: 'Erro de validação'),
        ]
    )]
    public function update(UpdateElementTypeRequest $request, ElementType $element): ElementResource
    {
        $element->update($request->validated());

        return new ElementResource($element);
    }

    #[OA\Delete(
        path: '/agent/elements/{id}',
        summary: 'Remover um elemento',
        description: 'Exclui permanentemente um elemento do sistema.',
        operationId: 'destroyElementAgent',
        tags: ['Agent - Elementos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'ID do elemento',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 204, description: 'Elemento removido com sucesso (Sem conteúdo)'),
            new OA\Response(response: 404, description: 'Elemento não encontrado'),
        ]
    )]
    public function destroy(ElementType $element): Response
    {
        $element->delete();

        return response()->noContent();
    }
}
