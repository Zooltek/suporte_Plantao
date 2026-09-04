<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class PrepareCategoryRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * No Laravel 12, mantemos true se o controle de acesso for via Middleware/Policy.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação aplicadas à requisição.
     */
    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'min:3', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }

    /**
     * Customização de nomes de atributos para mensagens de erro (opcional).
     */
    public function attributes(): array
    {
        return [
            'name'  => trans('ticketit/lang.name'),
            'color' => trans('ticketit/lang.color'),
        ];
    }
}
