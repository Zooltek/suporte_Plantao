<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrepareCommentStoreRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        // Geralmente true se você já usa Middlewares de autenticação nas rotas
        return true;
    }

    /**
     * Regras de validação aplicadas à requisição.
     */
    public function rules(): array
    {
        return [
            'ticket_id' => [
                'required',
                'integer',
                Rule::exists('ticketit', 'id'),
            ],
            'content' => [
                'required',
                'string',
                'min:6',
                'max:65535',
            ],
        ];
    }

    /**
     * Customização das mensagens de erro (Opcional).
     */
    public function messages(): array
    {
        return [
            'content.required' => trans('ticketit::lang.make-sure-to-include-content'),
            'content.min'      => trans('ticketit::lang.content-has-minimum-length', ['num' => 6]),
        ];
    }

    /**
     * Nomes amigáveis para os campos.
     */
    public function attributes(): array
    {
        return [
            'ticket_id' => trans('ticketit::lang.ticket'),
            'content'   => trans('ticketit::lang.comment'),
        ];
    }
}
