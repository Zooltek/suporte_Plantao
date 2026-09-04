<?php

namespace App\Http\Controllers\Helpdesk;

use App\Http\Controllers\Controller;
use App\Services\Helpdesk\BoletoService;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

class BoletoController extends Controller
{
    public function __construct(
        protected BoletoService $boletoService
    ) {
        $this->middleware('auth');
    }

    /**
     * Lista os boletos do cliente logado.
     */
    public function index(): View
    {
        $boletos = $this->boletoService->getBoletosByUserId(Auth::id());

        // Se a coleção estiver vazia, a View decide se mostra
        // "Nenhum boleto" ou "Empresa não vinculada"
        return view('helpdesk.boleto.index', [
            'boletos' => $boletos,
            'has_company' => Auth::user()->company !== null
        ]);
    }
}
