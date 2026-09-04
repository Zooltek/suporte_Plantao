<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Agent\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardService $dashboardService
    ) {}

    /**
     * Exibe o dashboard inicial.
     */
    public function index(Request $request): View
    {
        $data = $this->dashboardService->getIndexData($request->all());

        return view('agent.index', $data);
    }

    /**
     * Exibe a visão condensada com calendário de agendamentos e tickets.
     */
    public function calendarCondensed(Request $request): View
    {
        $data = $this->dashboardService->getCondensedData(
            Auth::guard('admin')->user(),
            $request->all()
        );

        return view('agent.condensed', $data);
    }

    /**
     * Tela de ajuda do agente.
     */
    public function helper(): View
    {
        return view('agent.helper');
    }
}