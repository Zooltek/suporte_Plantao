<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Services\Agent\ImplantacaoService;
use Illuminate\View\View;

class ImplantacaoController extends Controller
{
    public function __construct(
        private readonly ImplantacaoService $implantacaoService
    ) {}

    /**
     * Hub principal de implantação: KPIs + clientes em andamento.
     */
    public function index(): View
    {
        return view('agent.implantacao.index', [
            'stats'   => $this->implantacaoService->getStats(),
            'clients' => $this->implantacaoService->getClients(),
        ]);
    }

    /**
     * Lista de agendamentos com atividades de implantação.
     */
    public function schedules(): View
    {
        return view('agent.implantacao.schedules', [
            'schedules' => $this->implantacaoService->getSchedules(),
        ]);
    }
}
