<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CanManageImplementation
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user && $user->canManageImplementation()) {
            return $next($request);
        }

        abort(403, 'Você não possui permissão para acessar implantações.');
    }
}
