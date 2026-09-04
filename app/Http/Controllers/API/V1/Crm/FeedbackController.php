<?php

namespace App\Http\Controllers\API\V1\Crm;

use App\Http\Controllers\Controller;
use App\Http\Resources\Crm\FeedbackResource;
use App\Models\Crm\Feedback;
use App\Models\Ticket\Agent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class FeedbackController extends Controller
{
    #[OA\Get(
        path: '/api/v1/crm/feedbacks',
        summary: 'Listar feedbacks',
        description: 'Lista os feedbacks do CRM com filtros de data e relacionamentos dinâmicos',
        operationId: 'getCrmFeedbacks',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'customer_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'agent_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'order', in: 'query', required: false, schema: new OA\Schema(type: 'string', example: 'desc')),
            new OA\Parameter(name: 'origin', in: 'query', description: 'Incluir relação de origem', required: false, schema: new OA\Schema(type: 'boolean')),
            new OA\Parameter(name: 'status', in: 'query', description: 'Incluir relação de status', required: false, schema: new OA\Schema(type: 'boolean')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FeedbackResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $start = Carbon::parse($request->string('start', now()->toDateTimeString()))->startOfDay();
        $end = Carbon::parse($request->string('end', now()->toDateTimeString()))->endOfDay();

        $query = Feedback::query()
            ->with(['customer'])
            ->whereBetween('created_at', [$start, $end])
            ->whereNotNull('completed_at');

        $query->when($request->customer_id, fn ($q) => $q->where('company_id', $request->customer_id))
            ->when($request->agent_id, fn ($q) => $q->where('agent_id', $request->agent_id))
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->when($request->order, fn ($q) => $q->orderBy('completed_at', 'desc'));

        $relations = array_filter([
            $request->origin ? 'origin' : null,
            $request->status ? 'status' : null,
        ]);

        if (! empty($relations)) {
            $query->with($relations);
        }

        return FeedbackResource::collection($query->get());
    }

    #[OA\Get(
        path: '/api/v1/crm/feedbacks/{feedback}',
        summary: 'Mostrar feedback',
        operationId: 'getCrmFeedback',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'feedback', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/FeedbackResource'),
                    ]
                )
            ),
        ]
    )]
    public function show(Feedback $feedback): FeedbackResource
    {
        return new FeedbackResource($feedback->load(['customer', 'elements.type']));
    }

    #[OA\Get(
        path: '/api/v1/crm/feedbacks/count',
        summary: 'Contar feedbacks por agente',
        description: 'Retorna a contagem de feedbacks por agente do departamento 3 dentro do período',
        operationId: 'getCrmFeedbacksCount',
        tags: ['CRM'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
            new OA\Parameter(name: 'end', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date-time')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/FeedbackCountResource')),
                    ]
                )
            ),
        ]
    )]
    public function count(Request $request): AnonymousResourceCollection
    {
        $start = Carbon::parse($request->string('start', now()->toDateTimeString()))->startOfDay();
        $end = Carbon::parse($request->string('end', now()->toDateTimeString()))->endOfDay();

        $agents = Agent::where('department_id', 3)
            ->withCount(['feedbacks' => function ($query) use ($start, $end) {
                $query->whereBetween('completed_at', [$start, $end]);
            }])
            ->get();

        return FeedbackResource::collection($agents);
    }
}
