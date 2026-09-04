<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Autorização já verificada pelo middleware 'admin' nas rotas
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user),
            ],
            'role' => ['required', 'integer', 'in:1,2,3'], // 1=Agente, 2=Admin, 3=CRM — role=0 bloqueado (A01/OWASP)
            'department_id' => ['required', 'exists:user_department,id'],
            'password'                 => ['nullable', 'string', 'min:6', 'max:255'],
            'active'                   => ['sometimes', 'boolean'],
            'deployment_admin'         => ['sometimes', 'boolean'],
            'can_manage_implementation'=> ['sometimes', 'boolean'],
            'is_oncall'                => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome do usuário é obrigatório.',
            'name.min' => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required' => 'O endereço de e-mail é obrigatório.',
            'email.email' => 'Insira um endereço de e-mail válido.',
            'email.unique' => 'Este e-mail já está sendo utilizado por outro usuário.',
            'role.required' => 'Selecione um nível de acesso.',
            'role.in' => 'O nível de acesso selecionado é inválido.',
            'department_id.required' => 'O departamento é obrigatório.',
            'department_id.exists' => 'O departamento selecionado é inválido ou não existe.',
        ];
    }
}
