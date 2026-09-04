<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Services\Access\AccessService;

class DashboardController extends Controller
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Redireciona o usuário para o painel correto com base no seu nível de acesso.
     */
    public function index(): RedirectResponse
    {
        $user = auth()->user();

        // 1. Se for Administrador de Suporte/CRM
        if ($this->accessService->isAdmin($user)) {
            return redirect()->route('admin.api.v1.users.index'); // Ou sua rota principal de admin
        }

        // 2. Se for Agente de Suporte
        if ($this->accessService->isAgent($user)) {
            $target = $user->getSetting('agent_default_view', 'agent.index');
            return redirect()->route($target);
        }

        // 3. Usuário Comum / Cliente
        return redirect()->route('customer.dashboard');
    }
}
