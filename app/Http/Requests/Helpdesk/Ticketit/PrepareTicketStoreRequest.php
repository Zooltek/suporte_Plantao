<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class PrepareTicketStoreRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para a criação de tickets.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject'     => ['required', 'string', 'min:3', 'max:255'],
            'content'     => ['required', 'string', 'min:6'],
            'priority_id' => ['required', 'integer', 'exists:ticketit_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticketit_categories,id'],
            
            // Laravel 12: Uso de regras de objeto para arquivos (mais legível)
            'file' => [
                'nullable',
                File::types(['jpg', 'jpeg', 'pdf', 'png', 'bmp', 'rar', 'zip'])
                    ->max(5120), // 5MB
            ],
            
            // Campos adicionais que notamos na Controller anteriormente
            'company_id'      => ['nullable', 'integer', 'exists:customers,id'],
            'user_id'         => ['nullable', 'integer', 'exists:users,id'],
            'sub_category_id' => ['nullable', 'integer'],
            'obs'             => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Nomes amigáveis para os atributos em caso de erro.
     */
    public function attributes(): array
    {
        return [
            'subject'     => 'Assunto',
            'content'     => 'Conteúdo/Descrição',
            'priority_id' => 'Prioridade',
            'category_id' => 'Categoria',
            'file'        => 'Anexo',
        ];
    }
}
