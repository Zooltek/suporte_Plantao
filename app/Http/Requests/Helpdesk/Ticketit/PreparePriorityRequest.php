<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class PreparePriorityRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Obtém as regras de validação que se aplicam à requisição.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'color' => ['sometimes', 'string', 'hex_color'],
        ];
    }

    /**
     * Obtém os nomes personalizados dos atributos para mensagens de erro.
     */
    public function attributes(): array
    {
        return [
            'name'  => trans('ticketit::admin.priority-name'),
            'color' => trans('ticketit::admin.priority-color'),
        ];
    }
}
