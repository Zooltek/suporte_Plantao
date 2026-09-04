<?php

namespace App\Http\Controllers\API\V1\Agent;

use App\Http\Controllers\Controller;
use App\Services\AgentService;
use App\Http\Resources\AgentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AgentResource",
    title: "AgentResource",
    description: "Agent resource representation",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "email", type: "string"),
        new OA\Property(property: "department_id", type: "integer", nullable: true),
        new OA\Property(property: "department_name", type: "string"),
        new OA\Property(property: "last_tickets_count", type: "integer")
    ]
)]
class AgentController extends Controller
{
    public function __construct(
        protected AgentService $agentService
    ) {}

    #[OA\Get(
        path: "/api/agents",
        operationId: "getAgentsList",
        tags: ["Agents"],
        summary: "Lista todos os agentes ativos",
        parameters: [
            new OA\Parameter(name: "all", in: "query", description: "Trazer todos os agentes ou apenas ticketit_agents", schema: new OA\Schema(type: "boolean")),
            new OA\Parameter(name: "query", in: "query", description: "Termo de busca", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Sucesso", content: new OA\JsonContent(type: "array", items: new OA\Items(ref: "#/components/schemas/AgentResource")))
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $showAll = $request->boolean('all');
        $searchQuery = $request->input('query');

        $agents = $this->agentService->listAgents($showAll, $searchQuery);

        return response()->json(AgentResource::collection($agents));
    }

    #[OA\Get(
        path: "/api/agents/{id}",
        operationId: "getAgentById",
        tags: ["Agents"],
        summary: "Exibe um agente específico",
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Sucesso"),
            new OA\Response(response: 404, description: "Agente não encontrado")
        ]
    )]
    public function show(int $id): JsonResponse
    {
        // Mantendo findOrFail direto ou movendo para service dependendo da preferência
        // Se usar o model Ticket\Agent
        $agent = \App\Models\Ticket\Agent::findOrFail($id);

        return response()->json(new AgentResource($agent));
    }

    #[OA\Get(
        path: "/api/agents/ratings",
        operationId: "getAgentRatings",
        tags: ["Agents"],
        summary: "Retorna o ranking de avaliação dos agentes",
        responses: [
            new OA\Response(
                response: 200, 
                description: "Lista ordenada por ranking",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: "rating", type: "number", format: "float"),
                            new OA\Property(property: "count", type: "integer"),
                            new OA\Property(property: "agent", type: "object")
                        ]
                    )
                )
            )
        ]
    )]
    public function rating(): JsonResponse
    {
        $ratings = $this->agentService->getAgentRatings();
        return response()->json($ratings);
    }

    #[OA\Get(
        path: "/api/agents/birthdays",
        operationId: "getAgentBirthdays",
        tags: ["Agents"],
        summary: "Retorna os agentes aniversariantes do dia",
        responses: [
            new OA\Response(response: 200, description: "Sucesso")
        ]
    )]
    public function birthdays(): JsonResponse
    {
        $birthdays = $this->agentService->getBirthdays();
        return response()->json($birthdays);
    }
}