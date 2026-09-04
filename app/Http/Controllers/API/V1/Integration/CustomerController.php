<?php

namespace App\Http\Controllers\API\V1\Integration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Integration\StoreCustomerRequest;
use App\Http\Resources\Integration\CustomerIntegrationResource;
use App\Services\Integration\CustomerIntegrationService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integração com o sistema financeiro — cadastro e situação de clientes.
 *
 * Endpoints inbound: o sistema financeiro é o chamador (servidor-a-servidor),
 * autenticado por API key (cabeçalho X-API-Key). Os clientes são identificados
 * pelo id do financeiro (campo `financeiro_id`).
 */
class CustomerController extends Controller
{
    public function __construct(
        private readonly CustomerIntegrationService $service,
    ) {}

    #[OA\Post(
        path: '/api/v1/integration/customers',
        operationId: 'integrationCustomerStore',
        summary: 'Cadastro de novo cliente',
        description: 'Recebe os dados de um novo cliente enviados pelo sistema financeiro. Idempotente: reenviar o mesmo `id` atualiza o cliente existente.',
        security: [['integration_api_key' => []]],
        tags: ['Integração - Financeiro'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CustomerRegistrationPayload')
        ),
        responses: [
            new OA\Response(response: 201, description: 'Cliente criado.'),
            new OA\Response(response: 200, description: 'Cliente já existente atualizado (reenvio).'),
            new OA\Response(response: 401, description: 'API key ausente ou inválida.'),
            new OA\Response(response: 422, description: 'Falha de validação dos dados enviados.'),
        ]
    )]
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->register($request->validated());

        $status = $customer->wasRecentlyCreated
            ? Response::HTTP_CREATED
            : Response::HTTP_OK;

        return CustomerIntegrationResource::make($customer)
            ->response()
            ->setStatusCode($status);
    }

    #[OA\Patch(
        path: '/api/v1/integration/customers/{id}/inactivate',
        operationId: 'integrationCustomerInactivate',
        summary: 'Inativar contrato do cliente',
        description: 'Marca o cliente como inativo (is_active = false) quando o contrato for suspenso ou desativado pelo financeiro. O `id` é o id do cliente no sistema financeiro.',
        security: [['integration_api_key' => []]],
        tags: ['Integração - Financeiro'],
        parameters: [
            new OA\PathParameter(
                name: 'id',
                description: 'Id do cliente no sistema financeiro.',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 154
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente marcado como inativo.'),
            new OA\Response(response: 401, description: 'API key ausente ou inválida.'),
            new OA\Response(response: 404, description: 'Cliente não encontrado.'),
        ]
    )]
    public function inactivate(int $id): JsonResponse
    {
        $customer = $this->service->inactivate($id);

        return CustomerIntegrationResource::make($customer)->response();
    }

    #[OA\Patch(
        path: '/api/v1/integration/customers/{id}/reactivate',
        operationId: 'integrationCustomerReactivate',
        summary: 'Reativar contrato do cliente',
        description: 'Marca o cliente como ativo (is_active = true) quando o contrato for reativado pelo financeiro. O `id` é o id do cliente no sistema financeiro.',
        security: [['integration_api_key' => []]],
        tags: ['Integração - Financeiro'],
        parameters: [
            new OA\PathParameter(
                name: 'id',
                description: 'Id do cliente no sistema financeiro.',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 154
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Cliente marcado como ativo.'),
            new OA\Response(response: 401, description: 'API key ausente ou inválida.'),
            new OA\Response(response: 404, description: 'Cliente não encontrado.'),
        ]
    )]
    public function reactivate(int $id): JsonResponse
    {
        $customer = $this->service->reactivate($id);

        return CustomerIntegrationResource::make($customer)->response();
    }
}
