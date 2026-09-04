<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class NotificationBlinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Se o request enviar um 'user', validamos se é o ID do usuário logado
        if ($this->has('user')) {
            return Auth::check() && (int) $this->user === Auth::id();
        }

        return Auth::check();
    }

    /**
     * Regras de validação.
     */
    public function rules(): array
    {
        return [
            'user' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Prepara os dados para validação (Sanitização).
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('user') && Auth::check()) {
            $this->merge([
                'user' => Auth::id(),
            ]);
        }
    }
}
