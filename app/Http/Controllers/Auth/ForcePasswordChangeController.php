<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Trata a troca obrigatória de senha no primeiro acesso.
 *
 * Ativado quando um administrador cria um usuário com senha temporária
 * (must_change_password = true).
 */
class ForcePasswordChangeController extends Controller
{

    public function show(): View
    {
        return view('auth.force-password-change');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            'password.required'  => 'A nova senha é obrigatória.',
            'password.confirmed' => 'A confirmação de senha não confere.',
            'password.min'       => 'A nova senha deve ter pelo menos 8 caracteres.',
        ]);

        $isAdmin = auth('admin')->check();
        $user    = $isAdmin ? auth('admin')->user() : $request->user();

        $user->update([
            'password'             => $request->password,
            'must_change_password' => false,
        ]);

        if ($isAdmin) {
            auth('admin')->logout();
            $loginRoute = 'admin.login';
        } else {
            Auth::logout();
            $loginRoute = 'login';
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute)
            ->with('status', 'Sua nova senha está ativa. Use-a abaixo para entrar.');
    }
}
