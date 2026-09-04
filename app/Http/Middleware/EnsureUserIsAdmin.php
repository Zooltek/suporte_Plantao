<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        Gate::authorize('access-admin');
        return $next($request);
    }
}