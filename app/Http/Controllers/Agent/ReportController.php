<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Agent\ReportService;
use App\Services\Report\DepartmentReportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly DepartmentReportService $departmentReportService,
    ) {}

    /**
     * Roteador de relatórios por período
     */
    public function generate(Request $request): View|JsonResponse
    {
        $date = $request->date('date') ?? now();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $type = (int) ($request->input('type') ?: 1);

        return match ($type) {
            1 => view('agent.ticket.totalizadores.total-suporte', [
                'data' => $this->reportService->generateSuporteData($start, $end),
            ]),
            2 => view('agent.ticket.totalizadores.total-clientes', [
                'data' => $this->reportService->generateClientesData($start, $end),
            ]),
            default => response()->json(['error' => 'Tipo de relatório inválido'], 400),
        };
    }

    /**
     * Relatório unificado de clientes em implantação
     */
    public function implementationClients(): View
    {
        $data = $this->reportService->getImplementationClientsData();

        return view('agent.reports.implementation-clients', $data);
    }

    /**
     * Relatório consolidado de chamados agrupados por departamento.
     *
     * Acesso restrito a Admin via middleware na rota — controller também
     * valida defensivamente. Período controlado por ?range=7|30|90 dias
     * (default: 30).
     */
    public function byDepartment(Request $request): View
    {
        abort_unless($request->user('admin')?->isAdmin(), 403);

        $rangeDays = (int) $request->input('range', 30);
        $rangeDays = in_array($rangeDays, [7, 30, 90], true) ? $rangeDays : 30;

        $from = Carbon::now()->subDays($rangeDays)->startOfDay();
        $to = Carbon::now()->endOfDay();

        $report = $this->departmentReportService->buildReport($from, $to);

        return view('agent.reports.by-department', [
            'report' => $report,
            'rangeDays' => $rangeDays,
            'from' => $from,
            'to' => $to,
        ]);
    }
}
