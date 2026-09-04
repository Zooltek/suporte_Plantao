<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class HelpdeskAdmin
{
    /**
     * Verifica se o usuário admin é autorizado para acessar o Ticketit Admin:
     * - Deve estar autenticado na guard 'admin'
     * - Deve ter role 'admin' (Spatie) ou flag ticketit_admin como fallback
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('admin')->check()) {
            $admin = Auth::guard('admin')->user();

            if ($admin->hasRole('admin') || (bool) $admin->ticketit_admin) {
                return $next($request);
            }

            return redirect()->route('agent.index')
                ->with('error', 'Acesso restrito: Você não tem permissão para acessar o painel administrativo do Ticketit.');
        }

        return redirect()->route('login')
            ->with('error', 'Por favor, faça login para acessar esta área.');
    }
}
