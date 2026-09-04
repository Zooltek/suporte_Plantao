<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Implantacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RatGroupRequest extends FormRequest
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
        $groupId = $this->route('group')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('schedule_record_element_group', 'name')->ignore($groupId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'O nome do grupo é obrigatório.',
            'name.unique'   => 'Já existe um grupo com este nome.',
            'name.max'      => 'O nome não pode ter mais de 100 caracteres.',
        ];
    }
}
