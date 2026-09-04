<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class PrepareConfigurationStoreRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * No Laravel 12, o retorno bool é estritamente tipado.
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
            'slug'        => ['required', 'string', 'unique:ticketit_settings,slug'],
            'default'     => ['required'],
            'value'       => ['required'],
            'lang'        => ['nullable', 'string', 'max:5'],
            // 'name'     => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * Customização de nomes de atributos para mensagens de erro (opcional).
     */
    public function attributes(): array
    {
        return [
            'slug' => 'identificador (slug)',
            'lang' => 'idioma',
        ];
    }
}
