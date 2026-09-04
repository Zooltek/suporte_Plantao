<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Oncall\OncallAttendance;
use App\Models\Oncall\OncallShift;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OncallReportController extends Controller
{
    /**
     * Exibe o painel de relatórios de plantão no sistema local.
     */
    public function index(Request $request): View
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());
        $agentId = $request->query('agent_id');

        // 1. Agentes disponíveis para o filtro (plantonistas ou que já atenderam)
        $agents = User::query()
            ->where(function ($q) {
                $q->where('is_oncall', true)
                  ->orWhere('ticketit_agent', true);
            })
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        // 2. Consulta base de atendimentos do período
        $attQuery = OncallAttendance::with(['agent:id,name', 'customer:id,name,trade_name,codigo_empresarial', 'category:category_id', 'ticket:id'])
            ->whereDate('started_at', '>=', $startDate)
            ->whereDate('started_at', '<=', $endDate);

        if ($agentId) {
            $attQuery->where('user_id', $agentId);
        }

        $attendances = $attQuery->orderByDesc('started_at')->get();

        // 3. Consulta de turnos manuais (para domingos ou escalas registradas)
        $shiftQuery = OncallShift::with(['agent:id,name', 'attendances'])
            ->whereDate('started_at', '>=', $startDate)
            ->whereDate('started_at', '<=', $endDate);

        if ($agentId) {
            $shiftQuery->where('user_id', $agentId);
        }

        $shifts = $shiftQuery->orderByDesc('started_at')->get();

        // ─── RELATÓRIO 1: APURAÇÃO TRABALHISTA POR AGENTE ─────────────────────
        $targetAgents = $agentId ? $agents->where('id', $agentId) : $agents;
        $laborReportsByAgent = [];

        foreach ($targetAgents as $agent) {
            $agentAtts = $attendances->where('user_id', $agent->id);
            $agentShifts = $shifts->where('user_id', $agent->id);

            // Minutos trabalhados categorizados por dia da semana
            $minsWeekday = 0;   // Seg a Sex: x 1.50
            $minsSaturday = 0;  // Sábado: x 1.75
            $minsSunday = 0;    // Domingo e Feriado: x 2.00
            $totalWorkedMinutes = 0;

            foreach ($agentAtts as $att) {
                $m = (int) $att->effective_minutes;
                $totalWorkedMinutes += $m;
                $dayOfWeek = Carbon::parse($att->started_at)->dayOfWeek;

                if ($dayOfWeek === 0) {
                    $minsSunday += $m;
                } elseif ($dayOfWeek === 6) {
                    $minsSaturday += $m;
                } else {
                    $minsWeekday += $m;
                }
            }

            // Cálculo do Sobreaviso Bruto da Escala
            // - Seg a Sex: 3h (180 min) por dia em que houve plantão
            // - Sábado: 12h (720 min) por sábado
            // - Domingo: turnos manuais
            $grossStandbyMinutes = 0;
            $distinctDates = $agentAtts->pluck('started_at')->map(fn($d) => Carbon::parse($d)->toDateString())->unique();
            
            // Adiciona dias em que o agente atendeu
            foreach ($distinctDates as $dateStr) {
                $d = Carbon::parse($dateStr);
                if ($d->dayOfWeek >= 1 && $d->dayOfWeek <= 5) {
                    $grossStandbyMinutes += 180;
                } elseif ($d->dayOfWeek === 6) {
                    $grossStandbyMinutes += 720;
                }
            }

            // Adiciona turnos manuais de domingo
            foreach ($agentShifts as $s) {
                $start = Carbon::parse($s->started_at);
                if ($start->dayOfWeek === 0 && $s->ended_at) {
                    $grossStandbyMinutes += max(0, $start->diffInMinutes($s->ended_at));
                }
            }

            // Se o agente estiver filtrado isoladamente e não tiver atendimentos mas for o mês corrente,
            // podemos considerar ao menos o sobreaviso dos plantões registrados
            if ($grossStandbyMinutes === 0 && $agentShifts->isNotEmpty()) {
                $grossStandbyMinutes = (int) $agentShifts->sum('total_standby_minutes') + (int) $agentShifts->sum('total_worked_minutes');
            }

            $liquidStandbyMinutes = max(0, $grossStandbyMinutes - $totalWorkedMinutes);

            // Horas equivalentes apuradas com os fatores legais/CLT
            $eqStandbyHours = round(($liquidStandbyMinutes / 60) * 0.333333, 2);
            $eqWeekdayHours = round(($minsWeekday / 60) * 1.50, 2);
            $eqSaturdayHours = round(($minsSaturday / 60) * 1.75, 2);
            $eqSundayHours = round(($minsSunday / 60) * 2.00, 2);

            $totalPayableHours = round($eqStandbyHours + $eqWeekdayHours + $eqSaturdayHours + $eqSundayHours, 2);

            // Inclui no relatório se tiver horas ou chamados
            if ($grossStandbyMinutes > 0 || $totalWorkedMinutes > 0 || $agentAtts->count() > 0) {
                $laborReportsByAgent[] = [
                    'agent' => $agent,
                    'attendances_count' => $agentAtts->count(),
                    'gross_standby_formatted' => sprintf('%02dh %02dmin', floor($grossStandbyMinutes / 60), $grossStandbyMinutes % 60),
                    'liquid_standby_minutes' => $liquidStandbyMinutes,
                    'liquid_standby_formatted' => sprintf('%02dh %02dmin', floor($liquidStandbyMinutes / 60), $liquidStandbyMinutes % 60),
                    'worked_minutes' => $totalWorkedMinutes,
                    'worked_formatted' => sprintf('%02dh %02dmin', floor($totalWorkedMinutes / 60), $totalWorkedMinutes % 60),
                    'weekday_worked_formatted' => sprintf('%02dh %02dmin', floor($minsWeekday / 60), $minsWeekday % 60),
                    'saturday_worked_formatted' => sprintf('%02dh %02dmin', floor($minsSaturday / 60), $minsSaturday % 60),
                    'sunday_worked_formatted' => sprintf('%02dh %02dmin', floor($minsSunday / 60), $minsSunday % 60),
                    'eq_standby_hours' => $eqStandbyHours,
                    'eq_weekday_hours' => $eqWeekdayHours,
                    'eq_saturday_hours' => $eqSaturdayHours,
                    'eq_sunday_hours' => $eqSundayHours,
                    'total_payable_hours' => $totalPayableHours,
                ];
            }
        }

        // ─── RELATÓRIO 2: CLIENTES ATENDIDOS POR AGENTE ────────────────────────
        $clientsByAgent = [];
        foreach ($targetAgents as $agent) {
            $agentAtts = $attendances->where('user_id', $agent->id);
            if ($agentAtts->isEmpty()) continue;

            $groupedClients = $agentAtts->groupBy(function ($att) {
                return $att->customer_id ? 'id_' . $att->customer_id : 'fallback_' . ($att->customer_name_fallback ?: 'Avulso');
            });

            $clientList = [];
            foreach ($groupedClients as $group) {
                $first = $group->first();
                $clientName = $first->customer
                    ? ($first->customer->trade_name ?: $first->customer->name)
                    : ($first->customer_name_fallback ?: 'Cliente não identificado');

                $clientCode = $first->customer?->codigo_empresarial;
                $totalMins = $group->sum('duration_minutes');

                $clientList[] = [
                    'name' => $clientName,
                    'code' => $clientCode,
                    'calls_count' => $group->count(),
                    'total_minutes' => $totalMins,
                    'total_formatted' => sprintf('%02dh %02dmin', floor($totalMins / 60), $totalMins % 60),
                    'last_call' => Carbon::parse($group->max('started_at'))->format('d/m/Y H:i'),
                ];
            }

            usort($clientList, fn($a, $b) => $b['calls_count'] <=> $a['calls_count']);

            $clientsByAgent[] = [
                'agent' => $agent,
                'total_calls' => $agentAtts->count(),
                'total_minutes' => $agentAtts->sum('duration_minutes'),
                'clients' => $clientList,
            ];
        }

        // ─── RELATÓRIO 3: TOP CLIENTES QUE MAIS UTILIZAM O PLANTÃO ─────────────
        $topClients = [];
        $totalPlantaoCalls = max(1, $attendances->count());

        $groupedAllClients = $attendances->groupBy(function ($att) {
            return $att->customer_id ? 'id_' . $att->customer_id : 'fallback_' . ($att->customer_name_fallback ?: 'Avulso');
        });

        foreach ($groupedAllClients as $group) {
            $first = $group->first();
            $clientName = $first->customer
                ? ($first->customer->trade_name ?: $first->customer->name)
                : ($first->customer_name_fallback ?: 'Cliente Avulso');

            $clientCode = $first->customer?->codigo_empresarial;
            $callsCount = $group->count();
            $totalMins = $group->sum('duration_minutes');

            $topClients[] = [
                'name' => $clientName,
                'code' => $clientCode,
                'calls_count' => $callsCount,
                'percentage' => round(($callsCount / $totalPlantaoCalls) * 100, 1),
                'total_minutes' => $totalMins,
                'total_formatted' => sprintf('%02dh %02dmin', floor($totalMins / 60), $totalMins % 60),
                'resolved_count' => $group->where('is_resolved', true)->count(),
            ];
        }

        usort($topClients, fn($a, $b) => $b['calls_count'] <=> $a['calls_count'] ?: $b['total_minutes'] <=> $a['total_minutes']);

        // ─── TOTAIS GERAIS DO CARD TOPO ───────────────────────────────────────
        $grandTotalStandbyHours = array_sum(array_column($laborReportsByAgent, 'eq_standby_hours'));
        $grandTotalExtraHours = array_sum(array_column($laborReportsByAgent, 'eq_weekday_hours'))
            + array_sum(array_column($laborReportsByAgent, 'eq_saturday_hours'))
            + array_sum(array_column($laborReportsByAgent, 'eq_sunday_hours'));
        $grandTotalPayableHours = array_sum(array_column($laborReportsByAgent, 'total_payable_hours'));

        return view('admin.oncall.reports', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'agentId' => $agentId,
            'agents' => $agents,
            'attendances' => $attendances,
            'laborReportsByAgent' => $laborReportsByAgent,
            'clientsByAgent' => $clientsByAgent,
            'topClients' => $topClients,
            'totals' => [
                'total_calls' => $attendances->count(),
                'distinct_clients' => count($topClients),
                'standby_hours_payable' => $grandTotalStandbyHours,
                'extra_hours_payable' => round($grandTotalExtraHours, 2),
                'total_payable_hours' => round($grandTotalPayableHours, 2),
            ],
        ]);
    }

    /**
     * Exporta os atendimentos de plantão do período em CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->endOfMonth()->toDateString());
        $agentId = $request->query('agent_id');

        $query = OncallAttendance::with(['agent:id,name', 'customer:id,name,trade_name,codigo_empresarial', 'ticket:id'])
            ->whereDate('started_at', '>=', $startDate)
            ->whereDate('started_at', '<=', $endDate);

        if ($agentId) {
            $query->where('user_id', $agentId);
        }

        $attendances = $query->orderBy('started_at')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"relatorio-plantao-{$startDate}-a-{$endDate}.csv\"",
        ];

        return response()->stream(function () use ($attendances) {
            $output = fopen('php://output', 'w');
            // BOM UTF-8 para compatibilidade direta com Excel no Windows
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($output, [
                'Ticket #',
                'Data',
                'Dia da Semana',
                'Hora Início',
                'Hora Fim',
                'Duração (min)',
                'Agente',
                'Código Cliente',
                'Cliente',
                'Contato',
                'Problema',
                'Solução',
                'Resolvido?',
            ], ';');

            foreach ($attendances as $att) {
                $start = Carbon::parse($att->started_at);
                $end = Carbon::parse($att->ended_at);
                $days = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

                fputcsv($output, [
                    $att->ticket_id ? '#' . $att->ticket_id : 'Pendente',
                    $start->format('d/m/Y'),
                    $days[$start->dayOfWeek],
                    $start->format('H:i'),
                    $end->format('H:i'),
                    $att->duration_minutes,
                    $att->agent?->name ?: 'N/A',
                    $att->customer?->codigo_empresarial ?: '',
                    $att->customer ? ($att->customer->trade_name ?: $att->customer->name) : ($att->customer_name_fallback ?: 'Avulso'),
                    $att->contact_name ?: '',
                    $att->trouble ?: '',
                    $att->solution ?: '',
                    $att->is_resolved ? 'Sim' : 'Não',
                ], ';');
            }

            fclose($output);
        }, 200, $headers);
    }

    /**
     * Atualiza/ajusta um atendimento de plantão (ajuste de horas, glosa ou correção pelo gestor).
     */
    public function updateAttendance(Request $request, OncallAttendance $attendance)
    {
        $validated = $request->validate([
            'started_at' => ['required', 'date'],
            'ended_at' => ['required', 'date', 'after_or_equal:started_at'],
            'duration_minutes' => ['required', 'integer', 'min:0'],
            'adjusted_duration_minutes' => ['nullable', 'integer', 'min:0'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'trouble' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
        ]);

        $validated['is_approved'] = $request->has('is_approved');
        $attendance->update($validated);

        if ($attendance->shift) {
            $attendance->shift->recalculateHours();
        }

        return redirect()->back()->with('success', 'Atendimento de plantão ajustado com sucesso.');
    }

    /**
     * Exclui um atendimento lançado erroneamente ou duplicado.
     */
    public function destroyAttendance(OncallAttendance $attendance)
    {
        $shift = $attendance->shift;
        $attendance->delete();

        if ($shift) {
            $shift->recalculateHours();
        }

        return redirect()->back()->with('success', 'Atendimento de plantão excluído com sucesso.');
    }
}
