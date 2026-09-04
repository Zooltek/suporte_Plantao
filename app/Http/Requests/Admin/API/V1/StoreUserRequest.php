<?php

namespace App\Http\Requests\Admin\API\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'          => ['required', 'string', 'min:3', 'max:255'],
            'email'         => ['required', 'email', 'unique:users,email'],
            'role'          => ['required', 'in:1,2,3'], // 1=Agente, 2=Admin, 3=CRM — role=0 bloqueado (A01/OWASP)
            'department_id' => ['required', 'exists:user_department,id'],
            // Senha temporária opcional: mínimo 6 chars (sem exigir complexidade, pois é provisória)
            'password'      => ['nullable', 'string', 'min:6', 'max:255'],
            'is_oncall'     => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'         => 'O nome completo do usuário é obrigatório.',
            'name.min'              => 'O nome deve ter pelo menos 3 caracteres.',
            'email.required'        => 'O endereço de e-mail é obrigatório.',
            'email.email'           => 'Insira um endereço de e-mail válido.',
            'email.unique'          => 'Este e-mail já está cadastrado em nossa base.',
            'role.required'         => 'Você deve selecionar o nível de acesso (Role).',
            'role.in'               => 'O nível de acesso selecionado é inválido.',
            'department_id.required'=> 'A seleção de um departamento é obrigatória.',
            'department_id.exists'  => 'O departamento selecionado não existe no sistema.',
            'password.min'          => 'A senha temporária deve ter pelo menos 6 caracteres.',
        ];
    }
}
