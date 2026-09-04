<?php

declare(strict_types=1);

namespace App\Http\Requests\Tasks\Changelog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class VersionStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // verificar se o usuário está logado
        return Auth::check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'           => ['required', 'string', 'max:255'],
            'project_id'     => ['required', 'numeric', 'exists:projects,id'],
            'reference_date' => ['required', 'date', 'before_or_equal:today'],
            'time'           => ['nullable', 'string', 'regex:/^([01]?[0-9]|2[0-3]):[0-5][0-9]$/'],
        ];
    }

    /**
     * customiza as mensagens de erro para exibição no Agent/Admin.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'           => 'O nome da versão é obrigatório.',
            'project_id.exists'       => 'O projeto selecionado é inválido.',
            'reference_date.required' => 'A data de referência deve ser informada.',
            'reference_date.before_or_equal' => 'A data de referência não pode ser superior a hoje.',
            'time.regex'              => 'O formato da hora deve ser HH:mm.',
        ];
    }
}
