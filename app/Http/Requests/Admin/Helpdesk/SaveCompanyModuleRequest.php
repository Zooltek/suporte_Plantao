<?php

namespace App\Http\Requests\Admin\Helpdesk;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida criação e atualização de CompanyModuleType no painel admin.
 *
 * SRP: extrai a validação inline que estava em CompanyModuleController,
 * garantindo que o Controller delegue apenas roteamento e resposta HTTP.
 */
class SaveCompanyModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user('admin');
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'max:100'],
            'sort_order'    => ['nullable', 'integer', 'min:0'],
            'is_active'     => ['boolean'],
            'rat_module_id' => ['nullable', 'integer', 'exists:schedule_record_module,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'rat_module_id.exists' => 'O template RAT selecionado não existe.',
        ];
    }
}
