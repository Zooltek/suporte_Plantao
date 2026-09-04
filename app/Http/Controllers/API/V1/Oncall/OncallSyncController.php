<?php

namespace App\Http\Controllers\API\V1\Oncall;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Company;
use App\Models\Oncall\OncallAttendance;
use App\Models\Oncall\OncallShift;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OncallSyncController extends Controller
{
    /**
     * Retorna os dados mestres necessários para funcionamento 100% offline do app.
     * Envia apenas campos essenciais para performance e minimização de dados.
     */
    public function masterData(): JsonResponse
    {
        try {
            // 1. Clientes ativos
            $customers = Company::query()
                ->where('is_active', true)
                ->select([
                    'id',
                    'codigo_empresarial',
                    'trade_name',
                    'name',
                    'contact_name',
                    'phone',
                    'whatsapp_phone',
                ])
                ->orderBy('trade_name')
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'code' => $c->codigo_empresarial,
                        'trade_name' => $c->trade_name ?: $c->name,
                        'name' => $c->name,
                        'contact' => $c->contact_name,
                        'phone' => $c->whatsapp_phone ?: $c->phone,
                    ];
                });

            // 2. Categorias e Subcategorias ativas
            $categoriesRaw = Category::query()
                ->leftJoin('solutions_category_description', 'solutions_category.category_id', '=', 'solutions_category_description.category_id')
                ->select([
                    'solutions_category.category_id as id',
                    'solutions_category.parent_id',
                    'solutions_category_description.name',
                ])
                ->orderBy('solutions_category_description.name')
                ->get();

            $categories = $categoriesRaw->where('parent_id', 0)->values()->map(function ($cat) use ($categoriesRaw) {
                $subs = $categoriesRaw->where('parent_id', $cat->id)->values()->map(function ($sub) {
                    return [
                        'id' => $sub->id,
                        'name' => $sub->name ?: 'Subcategoria #'.$sub->id,
                    ];
                });

                return [
                    'id' => $cat->id,
                    'name' => $cat->name ?: 'Categoria #'.$cat->id,
                    'subcategories' => $subs,
                ];
            });

            // 3. Status de chamados
            $statuses = Status::query()
                ->select(['id', 'name', 'color', 'is_terminal', 'requires_solution'])
                ->get();

            // 4. Lista filtrada: apenas agentes habilitados para Plantão (flag is_oncall)
            $agents = User::query()
                ->where('active', true)
                ->where('is_oncall', true)
                ->select(['id', 'name', 'email'])
                ->orderBy('name')
                ->get();

            // Fallback de contingência: se ainda não houver nenhum plantonista flaggado,
            // traz os agentes ativos para manter o protótipo operacional até a configuração
            if ($agents->isEmpty()) {
                $agents = User::query()
                    ->where('active', true)
                    ->where('ticketit_agent', true)
                    ->select(['id', 'name', 'email'])
                    ->orderBy('name')
                    ->get();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'customers' => $customers,
                    'categories' => $categories,
                    'statuses' => $statuses,
                    'agents' => $agents,
                    'server_time' => now()->toIso8601String(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('[OncallSyncController@masterData] Erro ao carregar dados mestres: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar dados mestres: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Endpoint de sincronização de lote de plantões e atendimentos.
     * Suporta idempotência baseada em UUIDs gerados no celular.
     */
    public function sync(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'agent_id' => 'required|integer',
            'shifts' => 'nullable|array',
            'attendances' => 'nullable|array',
        ]);

        $agentId = (int) $payload['agent_id'];
        $shiftsData = $payload['shifts'] ?? [];
        $attendancesData = $payload['attendances'] ?? [];

        return DB::transaction(function () use ($agentId, $shiftsData, $attendancesData) {
            $shiftIdMap = [];
            $syncedShifts = [];
            $syncedAttendances = [];

            // 1. Processar turnos de plantão / sobreaviso
            foreach ($shiftsData as $sData) {
                if (empty($sData['uuid'])) {
                    continue;
                }

                $shift = OncallShift::updateOrCreate(
                    ['uuid' => $sData['uuid']],
                    [
                        'user_id' => $agentId,
                        'started_at' => Carbon::parse($sData['started_at']),
                        'ended_at' => ! empty($sData['ended_at']) ? Carbon::parse($sData['ended_at']) : null,
                        'total_standby_minutes' => (int) ($sData['total_standby_minutes'] ?? 0),
                        'total_worked_minutes' => (int) ($sData['total_worked_minutes'] ?? 0),
                        'status' => $sData['status'] ?? 'finished',
                        'notes' => $sData['notes'] ?? null,
                    ]
                );

                $shiftIdMap[$shift->uuid] = $shift->id;
                $syncedShifts[] = [
                    'uuid' => $shift->uuid,
                    'id' => $shift->id,
                    'status' => $shift->status,
                ];
            }

            // 2. Processar atendimentos realizados no plantão
            foreach ($attendancesData as $aData) {
                if (empty($aData['uuid'])) {
                    continue;
                }

                $shiftId = null;
                if (! empty($aData['shift_uuid'])) {
                    $shiftId = $shiftIdMap[$aData['shift_uuid']]
                        ?? OncallShift::where('uuid', $aData['shift_uuid'])->value('id');
                }

                $startedAt = Carbon::parse($aData['started_at']);
                $endedAt = Carbon::parse($aData['ended_at']);
                $durationMinutes = max(1, (int) ($aData['duration_minutes'] ?? $startedAt->diffInMinutes($endedAt)));

                $attendance = OncallAttendance::firstOrNew(['uuid' => $aData['uuid']]);
                $attendance->fill([
                    'oncall_shift_id' => $shiftId,
                    'user_id' => $agentId,
                    'customer_id' => ! empty($aData['customer_id']) ? (int) $aData['customer_id'] : null,
                    'customer_name_fallback' => $aData['customer_name_fallback'] ?? null,
                    'contact_name' => $aData['contact_name'] ?? null,
                    'category_id' => ! empty($aData['category_id']) ? (int) $aData['category_id'] : null,
                    'sub_category_id' => ! empty($aData['sub_category_id']) ? (int) $aData['sub_category_id'] : null,
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                    'duration_minutes' => $durationMinutes,
                    'trouble' => $aData['trouble'] ?? 'Chamado registrado via Plantão Mobile',
                    'solution' => $aData['solution'] ?? null,
                    'is_resolved' => ! empty($aData['is_resolved']),
                    'status_id' => ! empty($aData['status_id']) ? (int) $aData['status_id'] : 2,
                    'synced_at' => now(),
                ]);

                // Se o chamado ainda não virou ticket oficial em ticketit, gera agora!
                if (! $attendance->ticket_id) {
                    $ticket = new Ticket();
                    $ticket->agent_id = $agentId;
                    $ticket->author_id = $agentId;
                    $ticket->user_id = $agentId;
                    $ticket->company_id = $attendance->customer_id ?? 1; // Fallback para empresa 1 se for cliente avulso
                    $ticket->department_id = 1; // Suporte geral
                    $ticket->priority_id = 1;
                    $ticket->category_id = $attendance->category_id ?? 1;
                    $ticket->sub_category_id = $attendance->sub_category_id ?? 1;
                    $ticket->status_id = $attendance->status_id ?: 2;
                    
                    $contactText = $attendance->contact_name ?: ($attendance->customer_name_fallback ?: 'Plantão');
                    $ticket->contact = strtoupper($contactText);
                    $ticket->subject = 'Plantão: ' . $contactText;
                    $ticket->trouble = $attendance->trouble;
                    $ticket->content = $attendance->trouble;
                    $ticket->solution = $attendance->solution;
                    $ticket->obs = 'Gerado via App Mobile de Plantão. Duração: ' . $durationMinutes . ' min.';
                    $ticket->elapsed_time = (string) $durationMinutes;
                    $ticket->visible = 0;
                    $ticket->completed_at = $attendance->is_resolved ? $endedAt : null;
                    $ticket->save();

                    $attendance->ticket_id = $ticket->id;
                }

                $attendance->save();

                $syncedAttendances[] = [
                    'uuid' => $attendance->uuid,
                    'id' => $attendance->id,
                    'ticket_id' => $attendance->ticket_id,
                    'synced_at' => $attendance->synced_at->toIso8601String(),
                ];
            }

            // 3. Recalcular horas de sobreaviso para os turnos envolvidos
            foreach (array_unique(array_filter(array_values($shiftIdMap))) as $sId) {
                $shift = OncallShift::find($sId);
                if ($shift) {
                    $shift->recalculateHours();
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Sincronização realizada com sucesso!',
                'synced_shifts' => $syncedShifts,
                'synced_attendances' => $syncedAttendances,
            ]);
        });
    }

    /**
     * Relatório consolidado de horas de sobreaviso e atendimento por agente/período.
     */
    public function reports(Request $request): JsonResponse
    {
        $agentId = $request->query('agent_id');
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());

        $query = OncallShift::with(['agent:id,name', 'attendances'])
            ->whereDate('started_at', '>=', $startDate)
            ->whereDate('started_at', '<=', $endDate);

        if ($agentId) {
            $query->where('user_id', $agentId);
        }

        $shifts = $query->orderByDesc('started_at')->get();

        $totalStandbyMinutes = (int) $shifts->sum('total_standby_minutes');
        $totalWorkedMinutes = 0;
        $totalAttendancesCount = 0;

        // Totalizadores por tipo de hora extra (CLT / Acordo)
        $weekdayWorkedMinutes = 0;  // Segunda a Sexta (1.5x)
        $saturdayWorkedMinutes = 0; // Sábado (1.75x)
        $sundayWorkedMinutes = 0;   // Domingo e Feriados (2.0x)

        foreach ($shifts as $shift) {
            foreach ($shift->attendances as $att) {
                $totalAttendancesCount++;
                $mins = (int) $att->duration_minutes;
                $totalWorkedMinutes += $mins;

                $dayOfWeek = Carbon::parse($att->started_at)->dayOfWeek; // 0 = Domingo, 6 = Sábado
                if ($dayOfWeek === 0) {
                    $sundayWorkedMinutes += $mins;
                } elseif ($dayOfWeek === 6) {
                    $saturdayWorkedMinutes += $mins;
                } else {
                    $weekdayWorkedMinutes += $mins;
                }
            }
        }

        // Aplicação dos coeficientes trabalhistas
        // 1. Sobreaviso: 1/3 (0.333x)
        $standbyEquivalentMinutes = $totalStandbyMinutes * 0.333333;
        
        // 2. Horas extras com seus respectivos multiplicadores
        $weekdayEquivalentMinutes = $weekdayWorkedMinutes * 1.5;
        $saturdayEquivalentMinutes = $saturdayWorkedMinutes * 1.75;
        $sundayEquivalentMinutes = $sundayWorkedMinutes * 2.0;

        $totalPayableEquivalentMinutes = $standbyEquivalentMinutes 
            + $weekdayEquivalentMinutes 
            + $saturdayEquivalentMinutes 
            + $sundayEquivalentMinutes;

        return response()->json([
            'success' => true,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'labor_rules' => [
                'standby_multiplier' => 0.333,
                'weekday_multiplier' => 1.5,
                'saturday_multiplier' => 1.75,
                'sunday_multiplier' => 2.0,
            ],
            'totals' => [
                'raw' => [
                    'standby_minutes' => $totalStandbyMinutes,
                    'standby_formatted' => sprintf('%02dh %02dmin', floor($totalStandbyMinutes / 60), $totalStandbyMinutes % 60),
                    'worked_minutes' => $totalWorkedMinutes,
                    'worked_formatted' => sprintf('%02dh %02dmin', floor($totalWorkedMinutes / 60), $totalWorkedMinutes % 60),
                    'weekday_worked_minutes' => $weekdayWorkedMinutes,
                    'weekday_worked_formatted' => sprintf('%02dh %02dmin', floor($weekdayWorkedMinutes / 60), $weekdayWorkedMinutes % 60),
                    'saturday_worked_minutes' => $saturdayWorkedMinutes,
                    'saturday_worked_formatted' => sprintf('%02dh %02dmin', floor($saturdayWorkedMinutes / 60), $saturdayWorkedMinutes % 60),
                    'sunday_worked_minutes' => $sundayWorkedMinutes,
                    'sunday_worked_formatted' => sprintf('%02dh %02dmin', floor($sundayWorkedMinutes / 60), $sundayWorkedMinutes % 60),
                    'attendances_count' => $totalAttendancesCount,
                ],
                // Horas equivalentes apuradas (multiplicadas pelos fatores)
                'payable_equivalent' => [
                    'standby_equivalent_hours' => round($standbyEquivalentMinutes / 60, 2),
                    'weekday_equivalent_hours' => round($weekdayEquivalentMinutes / 60, 2),
                    'saturday_equivalent_hours' => round($saturdayEquivalentMinutes / 60, 2),
                    'sunday_equivalent_hours' => round($sundayEquivalentMinutes / 60, 2),
                    'total_payable_hours' => round($totalPayableEquivalentMinutes / 60, 2),
                    'total_payable_formatted' => sprintf('%02dh %02dmin', floor($totalPayableEquivalentMinutes / 60), round($totalPayableEquivalentMinutes % 60)),
                ],
            ],
            'shifts' => $shifts,
        ]);
    }
}
