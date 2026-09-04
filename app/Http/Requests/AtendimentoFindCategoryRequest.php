<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AtendimentoFindCategoryRequest extends FormRequest
{
    /**
     * Como o Controller já tem o middleware 'auth',
     * podemos apenas retornar true aqui.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Aqui definimos as regras para os campos que o Controller usa.
     */
    public function rules(): array
    {
        return [
            'category_id'   => ['required', 'integer'],
            'category_name' => ['nullable', 'string', 'max:255'],
            'agent'         => ['nullable'],
        ];
    }
}
