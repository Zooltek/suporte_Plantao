<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Ticket\Agent;
use App\Services\Agent\MonitorService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class MonitorController extends Controller
{
    public function __construct(
        private readonly MonitorService $monitorService
    ) {}

    public function index(Request $request): View
    {
        Carbon::setLocale('pt_BR');
        $now = now();

        if ($request->filled('start')) {
            $startStr = $request->input('start');
            session(['monitor.start' => $startStr]);
        } else {
            $startStr = session('monitor.start');
        }

        if ($request->filled('end')) {
            $endStr = $request->input('end');
            session(['monitor.end' => $endStr]);
        } else {
            $endStr = session('monitor.end');
        }

        $start = $startStr ? Carbon::createFromFormat('d/m/Y', $startStr)->startOfDay() : $now->copy()->startOfMonth();
        $end   = $endStr   ? Carbon::createFromFormat('d/m/Y', $endStr)->endOfDay()     : $now->copy()->endOfMonth();

        $agentsData = $this->monitorService->getAgentsData($start, $end, $now);
        
        $chartData = $request->boolean('compact') 
            ? ['labels' => [], 'data' => []] 
            : $this->monitorService->getChartData($start, $end, $now);

        return view('agent.monitor.index', [
            'agents'     => $agentsData, 
            'data'       => $agentsData,
            'chart_data' => $chartData,
            'start'      => $start,
            'end'        => $end,
        ]);
    }
}