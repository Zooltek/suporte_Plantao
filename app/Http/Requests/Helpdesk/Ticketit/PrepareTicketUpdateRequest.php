<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class PrepareTicketUpdateRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Regras de validação para a atualização de tickets.
     * * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject'     => ['required', 'string', 'min:3', 'max:255'],
            'content'     => ['required', 'string', 'min:6'],
            'priority_id' => ['required', 'integer', 'exists:ticketit_priorities,id'],
            'category_id' => ['required', 'integer', 'exists:ticketit_categories,id'],
            'status_id'   => ['required', 'integer', 'exists:ticketit_statuses,id'],
            
            /**
             * agent_id pode ser um ID (integer), 'none' ou 'auto'.
             * Usamos 'sometimes' caso o campo não seja enviado em todas as rotas de update.
             */
            'agent_id'    => ['required', 'string'],
        ];
    }

    /**
     * Nomes amigáveis para os atributos.
     */
    public function attributes(): array
    {
        return [
            'subject'     => 'Assunto',
            'content'     => 'Conteúdo',
            'priority_id' => 'Prioridade',
            'category_id' => 'Categoria',
            'status_id'   => 'Status',
            'agent_id'    => 'Agente',
        ];
    }
}
