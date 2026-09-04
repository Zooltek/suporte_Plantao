<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProfileFormRequest extends FormRequest
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
     * Regras de validação para o perfil do usuário.
     */
    public function rules(): array
    {
        $userId = Auth::guard('admin')->id() ?? Auth::id();

        return [
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId)
            ],
            'file'  => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            'remove' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensagens de erro
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Este e-mail já está sendo utilizado por outro usuário.',
            'file.image'   => 'O arquivo enviado deve ser uma imagem válida.',
        ];
    }
}
