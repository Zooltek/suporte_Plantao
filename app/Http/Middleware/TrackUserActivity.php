<?php

namespace App\Http\Middleware;

use App\Services\Auth\UserOnlineTracker;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Registra o usuário autenticado como ativo na sessão recente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('admin')->user()
            ?? Auth::guard('web')->user()
            ?? Auth::user();

        if ($user && isset($user->id)) {
            UserOnlineTracker::hit((int) $user->id);
        }

        return $next($request);
    }
}
