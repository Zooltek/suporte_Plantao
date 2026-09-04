<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Access\AccessService;
use Symfony\Component\HttpFoundation\Response;

class AgentMiddleware
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $user = Auth::guard('admin')->user();

            if ($this->accessService->canAccessAgent($user)) {
                return $next($request);
            }

            return redirect()->route('crm.index')
                ->with('error', 'Acesso restrito: Você não possui permissões de agente. Redirecionado para o seu painel.');
        }

        return redirect()->route('login')
            ->with('error', 'Por favor, faça login para acessar esta área.');
    }
}
