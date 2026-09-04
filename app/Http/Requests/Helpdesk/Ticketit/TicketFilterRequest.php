<?php

namespace App\Http\Requests\Helpdesk\Ticketit;

use Illuminate\Foundation\Http\FormRequest;

class TicketFilterRequest extends FormRequest
{
    /**
     * Determina se o usuário está autorizado a fazer esta requisição.
     * * @return bool
     */
    public function authorize(): bool
    {
        // Geralmente retorna true se a permissão for tratada no Controller ou Middleware
        return true;
    }

    /**
     * Regras de validação para os filtros.
     * Adicionei os campos que sua Controller utiliza para garantir integridade.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category' => ['nullable', 'integer', 'exists:ticketit_categories,id'],
            'status'   => ['nullable', 'integer', 'exists:ticketit_statuses,id'],
            'company'  => ['nullable', 'integer', 'exists:customers,id'],
            'agent'    => ['nullable', 'integer', 'exists:users,id'],
            'order'    => ['nullable', 'integer', 'in:1,2'],
            'q'        => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Mensagens personalizadas (Opcional).
     */
    public function messages(): array
    {
        return [
            'exists' => 'O filtro selecionado é inválido.',
            'integer' => 'O valor do filtro deve ser numérico.',
        ];
    }
}
