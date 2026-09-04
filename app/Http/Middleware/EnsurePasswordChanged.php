<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Intercepta sessões de usuários que devem trocar a senha no primeiro acesso.
 *
 * Ativado quando UserService::store() cria o usuário com senha temporária
 * definida pelo administrador (must_change_password = true).
 */
class EnsurePasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? auth('admin')->user();

        if ($user && $user->must_change_password) {
            $forcedRoute = route('password.force-change');

            // Permite a rota de troca de senha e o logout passarem sem redirecionamento
            if (!$request->routeIs('password.force-change', 'logout', 'admin.logout')) {
                return redirect($forcedRoute);
            }
        }

        return $next($request);
    }
}
