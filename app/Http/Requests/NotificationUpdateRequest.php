<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class NotificationUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        if ($this->has('user_id')) {
            return Auth::check() && (int) $this->user_id === Auth::id();
        }

        return Auth::check();
    }

    /**
     * Regras de validação para a atualização.
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Sanitização: Se o user_id não for enviado, assume o do usuário logado.
     */
    protected function prepareForValidation(): void
    {
        if (!$this->has('user_id') && Auth::check()) {
            $this->merge([
                'user_id' => Auth::id(),
            ]);
        }
    }
}
