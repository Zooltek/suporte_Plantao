<?php

namespace App\Http\Controllers\API\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\Crm\ElementResource;
use App\Services\API\V1\Crm\CrmElementService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class ElementController extends Controller
{
    public function __construct(
        protected CrmElementService $crmElementService
    ) {}

    #[OA\Get(
        path: '/api/v1/crm/elements',
        summary: 'Listar elementos CRM',
        description: 'Lista os elementos de formulário CRM indexados pelo nome (chave do objeto)',
        operationId: 'getCrmElements',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'form_id',
                in: 'query',
                description: 'ID do formulário',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, example: 1)
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Lista de elementos retornada com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            description: 'Elementos indexados por nome',
                            additionalProperties: new OA\AdditionalProperties(
                                ref: '#/components/schemas/ElementResource'
                            )
                        )
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado')
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $formId   = $request->integer('form_id', 1);
        $elements = $this->crmElementService->listByForm($formId);

        return ElementResource::collection($elements->keyBy('name'));
    }
}
