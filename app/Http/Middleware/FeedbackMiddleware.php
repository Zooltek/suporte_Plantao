<?php

namespace App\Http\Middleware;

use App\Services\Access\AccessService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class FeedbackMiddleware
{
    public function __construct(private readonly AccessService $accessService) {}

    /**
     * Trata a requisição de entrada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user() ?? Auth::user();

        if ($user) {
            if ($this->accessService->canAccessFeedback($user) || $user->ticketit_admin) {
                return $next($request);
            }

            Log::warning('Acesso ao módulo CRM negado (sem permissão)', [
                'user_id' => $user->id,
                'path'    => $request->path(),
            ]);
            return redirect()->route('crm.index')
                ->with('error', 'Acesso restrito: Você não possui permissões para acessar o módulo de Feedback.');
        }

        Log::warning('Acesso ao módulo CRM negado (não autenticado)', ['path' => $request->path()]);
        return redirect()->route('admin.login')
            ->with('error', 'Por favor, faça login para acessar esta área.');
    }
}
