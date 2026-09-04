<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Normalmente true, pois a autorização é tratada em Policies ou Gates
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,high,urgent'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'permalink' => ['required', 'string', 'max:255'],
            'department_id' => ['nullable', 'integer', 'exists:user_department,id'],
        ];
    }

    /**
     * Custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'priority.required' => 'O campo prioridade é obrigatório.',
            'priority.in' => 'A prioridade deve ser uma das opções: low, high ou urgent.',
            'name.string' => 'O nome deve ser um texto válido.',
            'name.max' => 'O nome não pode ultrapassar 255 caracteres.',
            'description.string' => 'A descrição deve ser um texto válido.',
            'parent_id.integer' => 'A categoria deve ser um número inteiro.',
            'parent_id.min' => 'A categoria informada é inválida.',

            'permalink.required' => 'O campo permalink é obrigatório.',
            'permalink.string' => 'O permalink deve ser um texto válido.',
            'permalink.max' => 'O permalink não pode ultrapassar 255 caracteres.',

            'department_id.integer' => 'O departamento informado é inválido.',
            'department_id.exists' => 'O departamento selecionado não existe.',
        ];
    }

    /**
     * Custom attributes for validation errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'priority' => 'prioridade',
            'parent_id' => 'categoria',
            'permalink' => 'link permanente',
            'department_id' => 'departamento',
        ];
    }
}
