<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCitiesRequest extends FormRequest
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
            'state_id' => ['required', 'integer', 'exists:states,id'],
        ];
    }

    /**
     * Mensagens respostas JSON.
     */
    public function messages(): array
    {
        return [
            'state_id.required' => 'O identificador do estado é obrigatório.',
            'state_id.integer'  => 'O identificador do estado deve ser um número.',
            'state_id.exists'   => 'Estado não encontrado em nossa base de dados.',
        ];
    }
}
