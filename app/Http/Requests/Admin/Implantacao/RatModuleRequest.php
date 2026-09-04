<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Implantacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RatModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $moduleId = $this->route('module')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('schedule_record_module', 'name')->ignore($moduleId),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'project'     => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do módulo é obrigatório.',
            'name.unique'   => 'Já existe um módulo com este nome.',
            'name.max'      => 'O nome não pode ter mais de 100 caracteres.',
        ];
    }
}
