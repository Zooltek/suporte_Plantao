<?php

namespace App\Http\Requests\Admin\Helpdesk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class SavePriorityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'O nome da prioridade é obrigatório.',
            'color.required' => 'A cor é obrigatória.',
            'color.regex'    => 'A cor deve estar no formato hexadecimal (ex: #6366f1).',
        ];
    }
}
