<?php

namespace App\Http\Requests\Agent\Settings;

use Illuminate\Foundation\Http\FormRequest;

class AgentSettingsUpdate extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'                => ['required', 'email', 'max:255'],
            'refresh_rate'         => ['required', 'integer', 'min:5'],
            'old_password'         => ['nullable', 'required_with:password', 'current_password'],
            'password'             => ['nullable', 'min:6', 'confirmed'],
            'open_ticket_new_tab'  => ['boolean'],
            'show_ticket_category' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'old_password.current_password' => 'A senha atual informada está incorreta.',
            'old_password.required_with'    => 'Informe sua senha atual para definir uma nova.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'open_ticket_new_tab'  => $this->boolean('open_ticket_new_tab'),
            'show_ticket_category' => $this->boolean('show_ticket_category'),
        ]);
    }
}