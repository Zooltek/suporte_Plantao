<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreRootCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:low,high,urgent'],
            'permalink' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['prohibited'],
            'department_id' => ['nullable', 'integer', 'exists:user_department,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome da categoria é obrigatório.',
            'name.string' => 'O nome da categoria deve ser um texto válido.',
            'name.max' => 'O nome da categoria deve ter no máximo 255 caracteres.',
            'priority.required' => 'A prioridade da categoria é obrigatória.',
            'priority.in' => 'A prioridade informada é inválida.',
            'permalink.string' => 'O permalink deve ser um texto válido.',
            'permalink.max' => 'O permalink deve ter no máximo 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'parent_id.prohibited' => 'O identificador de categoria não deve ser enviado neste fluxo.',
            'department_id.integer' => 'O departamento informado é inválido.',
            'department_id.exists' => 'O departamento selecionado não existe.',
        ];
    }
}
