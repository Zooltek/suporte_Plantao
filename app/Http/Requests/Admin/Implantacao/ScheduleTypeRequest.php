<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\Implantacao;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ScheduleTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'requires_customer' => $this->boolean('requires_customer'),
            'is_active'         => $this->has('is_active') ? $this->boolean('is_active') : true,
            'sort_order'        => $this->integer('sort_order'),
            'slug'              => trim((string) $this->input('slug', '')) ?: null,
            'label'             => trim((string) $this->input('label', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type   = $this->route('schedule_type');
        $typeId = $type?->id;
        $isSystem = (bool) ($type?->is_system ?? false);

        return [
            'slug' => [
                $isSystem ? 'nullable' : 'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('schedule_types', 'slug')->ignore($typeId),
            ],
            'label'             => ['required', 'string', 'max:80'],
            'requires_customer' => ['required', 'boolean'],
            'is_active'         => ['required', 'boolean'],
            'sort_order'        => ['required', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required'   => 'O slug do tipo é obrigatório.',
            'slug.alpha_dash' => 'O slug aceita apenas letras, números, traços e underlines.',
            'slug.unique'     => 'Já existe um tipo com este slug.',
            'label.required'  => 'O rótulo exibido é obrigatório.',
        ];
    }
}
