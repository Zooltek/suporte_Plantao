<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Services\Crm\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    public function index(Request $request): View
    {
        // 1. Passamos os dados brutos da Request para o Service processar
        $data = $this->dashboardService->getDashboardData(
            $request->input('start'),
            $request->input('form_id')
        );

        // 2. Retornamos a view com os dados processados
        return view('crm.index', $data);
    }
}