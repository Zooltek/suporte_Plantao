<?php

namespace App\Http\Controllers\API\V1\Schedule;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    /**
     * Roteador de relatórios utilizando Match Expression (PHP 8+).
     */
    public function generate(Request $request): View|JsonResponse
    {
        return match ($request->integer('report_id')) {
            1 => $this->clienteEspecifico($request),
            default => response()->json(['status' => false], 404),
        };
    }

    /**
     * Relatório de agendamentos de um cliente específico.
     */
    private function clienteEspecifico(Request $request): View|JsonResponse
    {
        // Correção para erro Stringable: usando input()
        $start = Carbon::parse($request->input('start', now()))->startOfDay();
        $end = Carbon::parse($request->input('end', now()))->endOfDay();

        if (!$request->filled('customer_id')) {
            return response()->json(['status' => 'Selecione um cliente'], 422);
        }

        $customer = Customer::findOrFail($request->customer_id);
        
        $schedules = Schedule::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('start_at', [$start, $end])
            ->orderByDesc('start_at')
            ->get();

        $data = [
            'title' => 'Agendamentos do cliente <b>' . e($customer->trade_name) . '</b>',
            'start' => $start->format('d/m/Y'),
            'end' => $end->format('d/m/Y'),
        ];

        return view('agent.schedule.reports.cliente-especifico', compact('data', 'schedules'));
    }

    /**
     * Relatório de Implantação.
     */
    public function implantacao(int $id, Request $request): View
    {
        return $this->processarImplantacao($id, $request, 'agent.schedule.reports.implantacao');
    }

    /**
     * Relatório de Implantação Detalhada.
     */
    public function implantacaoDetalhada(int $id, Request $request): View
    {
        return $this->processarImplantacao($id, $request, 'agent.schedule.reports.implantacao-detalhado', true);
    }

    /**
     * Lógica centralizada para evitar duplicidade de código (DRY).
     */
    private function processarImplantacao(int $id, Request $request, string $view, bool $detalhado = false): View
    {
        $customer = Customer::findOrFail($id);
        
        $query = Schedule::query()
            ->where('customer_id', $customer->id)
            ->with(['records'])
            ->orderBy('start_at', 'asc');

        if ($request->filled(['start', 'end'])) {
            $start = Carbon::parse($request->input('start'))->startOfDay();
            $end = Carbon::parse($request->input('end'))->endOfDay();
            $query->whereBetween('start_at', [$start, $end]);
        }

        $schedules = $query->get();
        $totalMinutosGeral = 0;

        foreach ($schedules as $schedule) {
            // Calcula minutos usando Collection para performance
            $minutosSchedule = $schedule->records->sum(function ($record) use ($detalhado) {
                $diff = $record->end->diffInMinutes($record->start);
                if ($detalhado) {
                    $record->hours = $this->convertToHoursMins((int) $diff, "%02d:%02d");
                }
                return $diff;
            });

            $schedule->hours = $this->convertToHoursMins((int) $minutosSchedule, "%02d:%02d");
            $totalMinutosGeral += $minutosSchedule;
        }

        $total_horas = $this->convertToHoursMins((int) $totalMinutosGeral);
        $data = []; // Mantido para compatibilidade com a view antiga

        return view($view, compact('data', 'schedules', 'customer', 'total_horas'));
    }

    /**
     * Converte minutos para formato legível.
     */
    public function convertToHoursMins(int $time, string $format = '%02d horas e %02d minutos'): ?string
    {
        if ($time < 1) {
            return null;
        }

        $hours = floor($time / 60);
        $minutes = ($time % 60);

        return sprintf($format, $hours, $minutes);
    }
}
