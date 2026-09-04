<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Agent\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardTvController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {}

    /**
     * Exibe o Dashboard TV se o token fornecido for válido.
     */
    public function show(Request $request): View
    {
        $this->authorizeToken($request);

        return view('admin.dashboard-tv', [
            'token' => $request->query('token'),
        ]);
    }

    /**
     * Retorna os dados em formato JSON para atualização via AJAX.
     */
    public function data(Request $request): JsonResponse
    {
        $this->authorizeToken($request);

        $data = $this->reportService->getDashboardTvData();

        return response()->json($data);
    }

    /**
     * Valida o token de acesso na query string.
     */
    private function authorizeToken(Request $request): void
    {
        $expectedToken = config('app.dashboard_tv_token', env('DASHBOARD_TV_TOKEN', 'amuratv2026'));
        $providedToken = $request->query('token');

        if (!$providedToken || $providedToken !== $expectedToken) {
            abort(403, 'Acesso não autorizado.');
        }
    }
}
