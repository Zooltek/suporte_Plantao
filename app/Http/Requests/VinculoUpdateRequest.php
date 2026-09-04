<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VinculoUpdateRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // Como o controller já usa o middleware 'auth', retornamos true.
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Removido o bloco Auth para corrigir o erro Sonar S1481
        return [
            'user'        => ['required', 'integer', 'exists:users,id'],
            'company'     => ['required', 'integer', 'exists:customers,id'],
            'responsible' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Mensagens amigáveis para o usuário.
     */
    public function messages(): array
    {
        return [
            'user.required'    => 'Selecione o usuário que deseja vincular.',
            'user.exists'      => 'O usuário selecionado é inválido.',
            'company.required' => 'Selecione a empresa para o vínculo.',
            'company.exists'   => 'A empresa selecionada não existe.',
        ];
    }
}
