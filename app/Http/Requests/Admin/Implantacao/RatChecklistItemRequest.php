<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Implantacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RatChecklistItemRequest extends FormRequest
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
        $itemId = $this->route('rat')?->id;

        return [
            'label'          => ['required', 'string', 'max:150'],
            'name'           => [
                'required',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('schedule_record_element_type', 'name')->ignore($itemId),
            ],
            'module_id'      => ['required', 'integer', 'exists:schedule_record_module,id'],
            'group_id'       => ['nullable', 'integer', 'exists:schedule_record_element_group,id'],
            'new_group_name' => ['nullable', 'string', 'max:100', 'required_without:group_id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'label.required'                => 'O rótulo do item é obrigatório.',
            'name.required'                 => 'O nome técnico é obrigatório.',
            'name.alpha_dash'                => 'O nome técnico aceita apenas letras, números, traços e underlines.',
            'name.unique'                   => 'Já existe um item com este nome técnico.',
            'module_id.required'            => 'Selecione um módulo.',
            'module_id.exists'              => 'Módulo inválido.',
            'group_id.exists'               => 'Grupo inválido.',
            'new_group_name.required_without' => 'Informe um grupo existente ou o nome de um novo grupo.',
        ];
    }

    /**
     * Retorna os dados validados com os tipos corretos.
     *
     * @return array{label: string, name: string, module_id: int, group_id: ?int, new_group_name: ?string}
     */
    public function toDto(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return [
            'label'          => (string) $validated['label'],
            'name'           => (string) $validated['name'],
            'module_id'      => (int) $validated['module_id'],
            'group_id'       => isset($validated['group_id']) ? (int) $validated['group_id'] : null,
            'new_group_name' => isset($validated['new_group_name']) ? (string) $validated['new_group_name'] : null,
        ];
    }
}
