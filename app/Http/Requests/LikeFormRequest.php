<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LikeFormRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para a avaliação.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'like' => ['required', 'integer', 'in:0,1'],
        ];
    }

    /**
     * Mensagens para o feedback.
     */
    public function messages(): array
    {
        return [
            'like.required' => 'A avaliação é obrigatória.',
            'like.in'       => 'A avaliação deve ser Like ou Dislike.',
        ];
    }
}
