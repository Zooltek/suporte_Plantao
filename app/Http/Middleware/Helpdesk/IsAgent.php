<?php

namespace App\Http\Middleware\Helpdesk;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\Access\AccessService;
use Symfony\Component\HttpFoundation\Response;

class IsAgent
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Trata a requisição de entrada para garantir acesso de Agente ou Admin.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->guest(route('login'));
        }

        $user = Auth::user();

        if ($this->accessService->canAccessAgent($user)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. Agent access required.'], 403);
        }

        abort(403, 'Acesso restrito a agentes do sistema.');
    }
}
