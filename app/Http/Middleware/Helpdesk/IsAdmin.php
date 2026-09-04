<?php

namespace App\Http\Middleware\Helpdesk;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Access\AccessService;
use Symfony\Component\HttpFoundation\Response;

class IsAdmin
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Trata uma requisição de entrada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check() && $this->accessService->isAdmin(Auth::user())) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. Admin access required.'], 403);
        }

        abort(403, 'Você não tem permissão para acessar esta área.');
    }
}
