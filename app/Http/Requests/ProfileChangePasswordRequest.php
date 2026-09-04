<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class ProfileChangePasswordRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // A07/OWASP: verifica ambos os guards — 'admin' (painel interno) e 'web' (fallback).
        return Auth::guard('admin')->check() || Auth::check();
    }

    /**
     * Regras de validação para a troca de senha.
     */
    public function rules(): array
    {
        return [
            'old_password' => ['required', 'string'],
            'password' => [
                'required',
                'confirmed',
                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'password_confirmation' => ['required'],
        ];
    }

    /**
     * Mensagens de erro amigáveis.
     */
    public function messages(): array
    {
        return [
            'password.confirmed' => 'A confirmação da nova senha não confere.',
            'password' => 'A nova senha deve ter pelo menos 8 caracteres e incluir letras, números e símbolos.',
        ];
    }
}
