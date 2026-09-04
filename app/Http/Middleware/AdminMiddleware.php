<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\Access\AccessService;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Protege rotas exclusivas para Administradores.
     */
    // App\Http\Middleware\AdminMiddleware.php

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user) {
            if ($this->accessService->isAdmin($user)) {
                return $next($request);
            }

            // Se for staff mas não admin, volta para o index de agentes
            if ($this->accessService->hasStaffAccess($user)) {
                Log::warning('Acesso admin negado (usuário sem permissão)', [
                    'user_id' => $user->id,
                    'path'    => request()->path(),
                ]);
                return redirect()->route('agent.index')
                    ->with('error', 'Acesso restrito: Você não possui permissões de administrador.');
            }
        }

        Log::warning('Acesso admin negado (não autenticado)', ['path' => request()->path()]);
        return redirect()->route('admin.login')
            ->with('error', 'Por favor, faça login para acessar esta área.');
    }
}
