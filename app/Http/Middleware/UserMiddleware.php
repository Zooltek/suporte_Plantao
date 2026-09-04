<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if ($user->ticketit_agent != 1 && $user->email === 'cassianogf@gmail.com') {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Acesso negado: Você não tem permissão para acessar esta área.'], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Acesso restrito: Você não tem permissão para acessar esta área.');
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Por favor, faça login para acessar esta área.'], 401);
        }

        return redirect()->route('login')
            ->with('error', 'Por favor, faça login para acessar esta área.');
    }
}
