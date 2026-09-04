<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['required', 'integer', 'min:1', 'exists:solutions_category,category_id'],
            'priority' => ['required', 'in:low,high,urgent'],
            'permalink' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:user_department,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da subcategoria é obrigatório.',
            'name.string' => 'O nome da subcategoria deve ser um texto válido.',
            'name.max' => 'O nome da subcategoria deve ter no máximo 255 caracteres.',
            'parent_id.required' => 'Selecione a categoria da subcategoria.',
            'parent_id.integer' => 'A categoria informada é inválida.',
            'parent_id.min' => 'A categoria informada é inválida.',
            'parent_id.exists' => 'A categoria selecionada não existe.',
            'priority.required' => 'A prioridade da subcategoria é obrigatória.',
            'priority.in' => 'A prioridade informada é inválida.',
            'permalink.string' => 'O permalink deve ser um texto válido.',
            'permalink.max' => 'O permalink deve ter no máximo 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'department_id.integer' => 'O departamento informado é inválido.',
            'department_id.exists' => 'O departamento selecionado não existe.',
        ];
    }
}
