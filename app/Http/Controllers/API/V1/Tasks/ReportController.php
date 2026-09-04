<?php

namespace App\Http\Controllers\API\V1\Tasks;

use App\Http\Controllers\Controller;
use App\Services\API\V1\Tasks\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Gera o relatório Carlos (Tarefas agrupadas por Módulo).
     */
    public function carlos(): View
    {
        $reportData = $this->reportService->getCarlosReportData();

        return view('tasks.report', $reportData);
    }

    /**
     * Gera o relatório "Lista de Solicitações" agrupado por Projeto → Cliente.
     * Aceita ?project_id=N para filtrar por projeto específico.
     */
    public function porCliente(Request $request): View
    {
        $data = $this->reportService->getClientReportData(
            $request->filled('project_id') ? (int) $request->project_id : null
        );

        return view('tasks.reports.por-cliente', compact('data'));
    }

    /**
     * Gera o relatório "Lista de Solicitações por Etiqueta" agrupado por Projeto → Label.
     * Aceita ?project_id=N para filtrar por projeto específico.
     */
    public function porModulo(Request $request): View
    {
        $data = $this->reportService->getModuleReportData(
            $request->filled('project_id') ? (int) $request->project_id : null
        );

        return view('tasks.reports.por-modulo', compact('data'));
    }
}