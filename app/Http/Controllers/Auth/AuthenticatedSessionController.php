<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Access\AccessService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'email' => \Illuminate\Support\Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        $isAdminContext = $request->routeIs('admin.*') || $request->is('admin/*');
        $guard = $isAdminContext ? 'admin' : 'web';

        // Filtra usuários desativados diretamente no attempt()
        $credentials['active'] = true;

        if (Auth::guard($guard)->attempt($credentials, $request->boolean('remember'))) {
            $user = Auth::guard($guard)->user();

            \App\Services\Auth\UserOnlineTracker::hit((int) $user->id);

            $request->session()->regenerate();

            if ($isAdminContext) {
                // Somente staff ou CRM podem usar o guard admin
                if (!$this->accessService->hasStaffAccess($user) && !$this->accessService->isCrmDepartment($user)) {
                    \App\Services\Auth\UserOnlineTracker::forget((int) $user->id);
                    Auth::guard($guard)->logout();
                    return back()->withErrors(['email' => 'Acesso negado: você não tem permissões administrativas ou de CRM.']);
                }

                $url = route($this->accessService->adminGuardRedirectRoute($user));
                return redirect()->intended($url);
            }

            // Staff/CRM: migra automaticamente para o guard admin
            if ($this->accessService->hasStaffAccess($user) || $this->accessService->isCrmDepartment($user)) {
                Auth::guard('admin')->login($user, $request->boolean('remember'));
                Auth::guard('web')->logout();
                $url = route($this->accessService->adminGuardRedirectRoute($user));
                return redirect()->intended($url);
            }

            return redirect()->route('portal.home');
        }

        // Distingue conta inativa de credenciais erradas para feedback mais claro
        $inactiveUser = User::where('email', $request->email)->first();
        if ($inactiveUser && Hash::check($request->password, $inactiveUser->password) && ! $inactiveUser->active) {
            return back()->withErrors(['email' => 'Sua conta está desativada. Entre em contato com o administrador.']);
        }

        return back()->withErrors(['email' => 'Credenciais inválidas.']);
    }


    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $userId = Auth::guard('admin')->id() ?? Auth::guard('web')->id() ?? Auth::id();
        if ($userId) {
            \App\Services\Auth\UserOnlineTracker::forget((int) $userId);
        }

        // Efetua logout no guard autenticado (admin ou web)
        if (Auth::guard('admin')->check()) {
            Auth::guard('admin')->logout();
        }

        if (Auth::guard('web')->check()) {
            Auth::guard('web')->logout();
        }

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
