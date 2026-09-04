<?php

namespace App\Http\Requests\Agent\Tickets;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'min:2', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'A resposta não pode estar vazia.',
            'content.min'      => 'A resposta deve ter pelo menos 2 caracteres.',
            'content.max'      => 'A resposta não deve ultrapassar 5000 caracteres.',
        ];
    }
}
