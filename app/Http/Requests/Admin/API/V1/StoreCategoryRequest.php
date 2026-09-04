<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Geralmente true, pois a autorização é tratada em Policies ou Gates
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
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'min:0'],
            'priority' => ['required', 'in:low,high,urgent'],
            'permalink' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
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
            'name.required' => 'O campo nome é obrigatório.',
            'name.string' => 'O nome deve ser um texto válido.',
            'name.max' => 'O nome não pode ultrapassar 255 caracteres.',

            'priority.required' => 'O campo prioridade é obrigatório.',
            'priority.in' => 'A prioridade deve ser low, high ou urgent.',

            'parent_id.integer' => 'O campo pai deve ser um número inteiro.',
            'parent_id.min' => 'A categoria informada é inválida.',

            'permalink.string' => 'O permalink deve ser um texto válido.',
            'permalink.max' => 'O permalink não pode ultrapassar 255 caracteres.',

            'description.string' => 'A descrição deve ser um texto válido.',

            'department_id.integer' => 'O departamento informado é inválido.',
            'department_id.exists' => 'O departamento selecionado não existe.',
        ];
    }
}
