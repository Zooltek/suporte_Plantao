<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Schedule;

use App\Http\Controllers\Agent\NotificationController;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Schedule\Element;
use App\Models\Schedule\Record;
use App\Services\Agent\DashboardService;
use GuzzleHttp\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

class ScheduleController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected NotificationController $notificationController
    ) {}

    #[OA\Get(
        path: '/api/v1/schedules',
        summary: 'Listar agendamentos',
        description: 'Retorna uma lista de agendamentos a partir de uma data específica usando o calendário do agente.',
        operationId: 'getSchedules',
        tags: ['Agendamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(
                name: 'start',
                in: 'query',
                description: 'Data de início (Y-m-d H:i:s)',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date-time', example: '2026-02-20 00:00:00')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/ScheduleResource')),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
        ]
    )]
    public function index(Request $request): AnonymousResourceCollection|JsonResponse
    {
        $start = Carbon::parse($request->input('start', now()->toDateTimeString()));

        $calendar = $this->dashboardService->getSchedulesCalendar($start, Auth::guard('admin')->user());

        return response()->json($calendar['data']);
    }

    #[OA\Post(
        path: '/api/v1/schedules/{id}/finalize',
        summary: 'Finalizar agendamento',
        description: 'Marca um agendamento como finalizado (status fin). Requer atividades (Records) ativas.',
        operationId: 'finalizeSchedule',
        tags: ['Agendamentos'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Finalizado com sucesso',
                content: new OA\JsonContent(properties: [new OA\Property(property: 'status', type: 'boolean', example: true)])
            ),
            new OA\Response(
                response: 422,
                description: 'Erro de validação (Sem atividades ou já finalizado)',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'status', type: 'boolean', example: false),
                        new OA\Property(property: 'msg', type: 'string', example: 'Adicione pelo menos uma atividade'),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Agendamento não encontrado'),
        ]
    )]
    public function finalize(int $id): JsonResponse
    {
        $schedule = Schedule::findOrFail($id);

        if (Record::active()->where('schedule_id', $id)->doesntExist()) {
            return response()->json(['status' => false, 'msg' => 'Adicione pelo menos uma atividade'], 422);
        }

        if ($schedule->status === 'fin') {
            return response()->json(['status' => false, 'msg' => 'Agendamento já finalizado'], 422);
        }

        $schedule->update(['status' => 'fin']);
        $this->notificationController->syncSchedule($schedule);

        return response()->json(['status' => true]);
    }

    #[OA\Get(
        path: '/api/v1/schedules/import',
        summary: 'Importar agendamentos externos',
        description: 'Sincroniza dados de uma API externa para o banco local.',
        operationId: 'importSchedules',
        tags: ['Agendamentos'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumo da importação',
                content: new OA\MediaType(
                    mediaType: 'text/plain',
                    schema: new OA\Schema(type: 'string', example: 'Novos Registros: 15 | Novos Elementos: 45')
                )
            ),
            new OA\Response(response: 500, description: 'Erro interno na importação'),
        ]
    )]
    public function import(Request $request): string
    {
        try {
            $client = new Client(['verify' => false]);
            $response = $client->get('https://sistemaplenus.com.br/panel/public/api/schedules', ['query' => ['records' => true]]);
            $schedules = json_decode((string) $response->getBody());

            $stats = ['records' => 0, 'elements' => 0];

            foreach ($schedules as $extSchedule) {
                $this->processExternalSchedule($extSchedule, $request, $stats);
            }

            return "Novos Registros: {$stats['records']} | Novos Elementos: {$stats['elements']}";
        } catch (\Exception $e) {
            Log::error('Falha na importação: '.$e->getMessage());

            return 'Erro ao importar dados.';
        }
    }

    /**
     * Processa cada agendamento externo individualmente.
     */
    private function processExternalSchedule(object $extSchedule, Request $request, array &$stats): void
    {
        foreach ($extSchedule->records as $extRecord) {
            if ($extRecord->legacy_id !== null) {
                continue;
            }

            DB::transaction(function () use ($extRecord, $extSchedule, $request, &$stats) {
                $record = $this->upsertRecord($extRecord, $extSchedule, $request, $stats);

                foreach ($extRecord->elements as $extElement) {
                    $this->upsertElement($extElement, $record->id, $stats);
                }

                Schedule::where('id', $extSchedule->legacy_id)->update(['status' => $extSchedule->status]);
            });
        }
    }

    /**
     * Cria ou atualiza um Registro (Record).
     */
    private function upsertRecord(object $extRecord, object $extSchedule, Request $request, array &$stats): Record
    {
        $record = Record::firstOrNew(['legacy_id' => $extRecord->id]);

        if (! $record->exists) {
            $stats['records']++;
        }

        $record->fill([
            'schedule_id' => $extSchedule->legacy_id,
            'customer_id' => $extSchedule->customer_id ?? $request->customer_id,
            'module_id' => $extRecord->module_id,
            'start' => Carbon::parse($extRecord->start),
            'end' => Carbon::parse($extRecord->end),
            'agent_id' => $extRecord->agent_id,
            'contact' => $extRecord->contact,
            'obs' => $extRecord->obs,
            'version' => $extRecord->version,
            'release' => $extRecord->release,
        ])->save();

        return $record;
    }

    /**
     * Cria ou atualiza um Elemento.
     */
    private function upsertElement(object $extElement, int $recordId, array &$stats): void
    {
        $element = Element::firstOrNew(['legacy_id' => $extElement->id]);

        if (! $element->exists) {
            $stats['elements']++;
        }

        $element->fill([
            'element_id' => $extElement->element_id,
            'record_id' => $recordId,
            'value' => $extElement->value,
        ])->save();
    }
}
