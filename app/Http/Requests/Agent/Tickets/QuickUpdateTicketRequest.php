<?php

namespace App\Http\Requests\Agent\Tickets;

use App\Models\Ticket\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Valida atualizações pontuais de status ou agente a partir da tela de detalhe
 * do chamado (quick update). Aceita apenas um dos dois campos por request,
 * sem exigir o payload completo do SaveTicketRequest.
 */
class QuickUpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Ticket $ticket */
        $ticket = $this->route('ticket');

        return $this->user('admin')?->can('update', $ticket) ?? false;
    }

    public function rules(): array
    {
        return [
            'status_id' => ['sometimes', 'required', 'integer', 'exists:ticketit_statuses,id'],
            'agent_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'department_id' => ['sometimes', 'nullable', 'integer', 'exists:user_department,id'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:solutions_category,category_id'],
            'sub_category_id' => ['sometimes', 'nullable', 'integer', 'exists:solutions_category,category_id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->has('status_id') && ! $this->has('agent_id') && ! $this->has('department_id') && ! $this->has('category_id')) {
                    $validator->errors()->add(
                        'field',
                        'Informe status_id, agent_id, department_id ou category_id para atualizar o chamado.'
                    );
                }

                if ($this->has('department_id') && ! $this->user('admin')?->can('changeDepartment', $this->route('ticket'))) {
                    $validator->errors()->add(
                        'department_id',
                        'Você não tem permissão para alterar o departamento deste chamado.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.exists' => 'Status inválido.',
            'agent_id.exists' => 'Agente inválido.',
            'department_id.exists' => 'Departamento inválido.',
            'category_id.exists' => 'Categoria inválida.',
            'category_id.required' => 'Selecione uma categoria.',
            'sub_category_id.exists' => 'Subcategoria inválida.',
        ];
    }
}
