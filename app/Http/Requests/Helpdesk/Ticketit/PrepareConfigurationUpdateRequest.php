<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class PrepareConfigurationUpdateRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'value'     => ['required', 'string'],
            'lang'      => ['nullable', 'string', 'max:5'],
            'serialize' => ['sometimes', 'boolean'],
            'password'  => ['required_if:serialize,1', 'string'],
        ];
    }

    /**
     * Mensagens de erro personalizadas.
     */
    public function messages(): array
    {
        return [
            'password.required_if' => 'A senha de administrador é necessária para salvar configurações serializadas.',
        ];
    }
}
