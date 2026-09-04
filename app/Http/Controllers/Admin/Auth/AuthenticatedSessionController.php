<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Services\Access\AccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly AccessService $accessService
    ) {}

    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->merge([
            'email' => \Illuminate\Support\Str::lower(trim((string) $request->input('email'))),
        ]);

        $credentials = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Auto-complete do domínio se necessário
        if (!str_contains($credentials['email'], '@')) {
            $credentials['email'] .= '@consuldatasistemas.com.br';
        }

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'As credenciais fornecidas não correspondem a nossos registros.',
            ]);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        $redirect = $this->accessService->adminLoginRedirectUrl($user);

        return redirect()->to($redirect);
    }


    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
