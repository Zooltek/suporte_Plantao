<?php

declare(strict_types=1);

namespace App\Http\Requests\API\Tasks\Changelog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class ChangelogStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        // garante que apenas usuários autenticados possam realizar postagens
        return Auth::check();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => [
                'required',
                'integer',
                'exists:projects,id'
            ],
            'content' => [
                'required',
                'string',
                'min:5'
            ],
            'sort_order' => [
                'nullable',
                'integer'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'É necessário informar o projeto.',
            'project_id.exists'   => 'O projeto selecionado é inválido.',
            'content.required'    => 'O conteúdo do changelog não pode ficar em branco.',
            'content.min'         => 'O conteúdo deve ter pelo menos 5 caracteres.',
        ];
    }
}
