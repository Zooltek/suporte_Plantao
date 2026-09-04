<?php

namespace App\Http\Controllers\API\V1\Schedule;

use App\Http\Controllers\Agent\NotificationController;
use App\Http\Controllers\Controller;
use App\Http\Resources\Schedule\RecordResource;
use App\Models\Schedule\Record;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class RecordController extends Controller
{
    public function __construct(
        protected NotificationController $notificationController
    ) {}

    #[OA\Get(
        path: '/api/v1/schedule/records',
        summary: 'Listar registros de agenda',
        description: 'Retorna uma lista de registros de agenda filtrados por período e opcionalmente por cliente',
        operationId: 'listScheduleRecords',
        tags: ['Schedule Records'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'start',
                in: 'query',
                description: 'Data de início (Y-m-d)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date-time', example: '2026-02-01')
            ),
            new OA\Parameter(
                name: 'end',
                in: 'query',
                description: 'Data de fim (Y-m-d)',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date-time', example: '2026-02-28')
            ),
            new OA\Parameter(
                name: 'customer_id',
                in: 'query',
                description: 'ID do cliente para filtro',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/RecordResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request, int $schedule): AnonymousResourceCollection
    {
        $start = Carbon::parse($request->input('start'))->startOfDay();
        $end = Carbon::parse($request->input('end'))->endOfDay();

        $query = Record::query()
            ->active()
            ->where('schedule_id', $schedule)
            ->with(['agent', 'module', 'customer', 'schedule.customer'])
            ->whereBetween('start', [$start, $end])
            ->orderBy('start', 'desc');

        $query->when($request->customer_id, function ($q) use ($request) {
            $q->whereHas('schedule', function ($sub) use ($request) {
                $sub->where('customer_id', $request->customer_id);
            });
        });

        return RecordResource::collection($query->get());
    }

    #[OA\Get(
        path: '/api/v1/schedule/records/{record}',
        summary: 'Exibir registro específico',
        operationId: 'showScheduleRecord',
        tags: ['Schedule Records'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'record', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/RecordResource'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Não encontrado'),
        ]
    )]
    public function show(int $schedule, Record $record): RecordResource
    {
        return new RecordResource($record->load(['elements.type']));
    }

    #[OA\Post(
        path: '/api/v1/schedule/records/{record}/sync',
        summary: 'Sincronizar registro',
        operationId: 'syncScheduleRecord',
        tags: ['Schedule Records'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'record', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sincronização iniciada'),
                    ]
                )
            ),
        ]
    )]
    public function sync(int $schedule, Record $record): JsonResponse
    {
        $this->notificationController->syncRecord($record);

        return response()->json(['message' => 'Sincronização iniciada']);
    }
}
