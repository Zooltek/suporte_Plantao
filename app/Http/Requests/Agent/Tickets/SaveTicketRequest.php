<?php

namespace App\Http\Requests\Agent\Tickets;

use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Support\Tickets\TicketStatusCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SaveTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        if ($ticket instanceof Ticket) {
            return (bool) $this->user('admin')?->can('update', $ticket);
        }

        return true; // store (create) — qualquer agente autenticado
    }

    public function rules(): array
    {
        $effectiveStatusId = $this->resolveEffectiveStatusId();

        $rules = [
            'company_id' => ['required', 'exists:customers,id'],
            'status_id' => ['required', 'integer', 'exists:ticketit_statuses,id'],
            'category_id' => ['required', 'exists:solutions_category,category_id'],
            'sub_category_id' => ['required', 'exists:solutions_category,category_id'],
            'origin_id' => ['nullable', 'integer', 'exists:ticketit_origin,id'],
            'contact' => ['required', 'string', 'max:255'],
            'trouble' => ['nullable', 'string'],
            'solution' => ['nullable', 'string'],
            'obs' => ['nullable', 'string'],
            'elapsed_time' => ['nullable', 'string'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
            'department_id' => ['nullable', 'integer', 'exists:user_department,id'],
            'auto_close' => ['boolean'],
            'is_recurring' => ['boolean'],
            'referenced_ticket_id' => ['nullable', 'integer', 'exists:ticketit,id'],
            'extra_categories' => ['nullable', 'array'],
            'extra_categories.*.category_id' => ['required_with:extra_categories', 'integer'],
            'extra_categories.*.sub_category_id' => ['nullable', 'integer'],
            'module_id' => ['nullable', 'integer', 'exists:company_module_types,id'],
            'rat_module_id' => ['nullable', 'integer', 'exists:schedule_record_module,id'],
            'rat_responses' => ['nullable', 'array'],
            'rat_responses.*' => ['nullable', 'string'],
            'version' => ['nullable', 'string'],
            'release' => ['nullable', 'string'],
            'created_at' => ['nullable', 'date'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['integer'],
            'arquivo' => ['nullable', 'array'],
            'arquivo.*' => ['file', 'max:20480'],
        ];

        // Status que exigem agente (Em Andamento, Visita Técnica, Resolvido, Não Resolvido):
        // exigem descrição do problema e atribuição de responsável.
        if (Status::requiresAgent($effectiveStatusId)) {
            $rules['trouble'] = ['required', 'string'];
            $rules['agent_id'] = ['required', 'integer', 'exists:users,id'];
        }

        // Solução obrigatória apenas quando o status semanticamente a exige
        // (ex: Resolvido). Em Andamento e Não Resolvido não possuem solução.
        if (Status::requiresSolution($effectiveStatusId)) {
            $rules['solution'] = ['required', 'string'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status_id.exists' => 'Selecione um status válido.',
            'origin_id.exists' => 'Selecione um canal de origem válido.',
            'agent_id.exists' => 'Selecione um técnico/suporte válido.',
            'department_id.exists' => 'Selecione um setor válido.',
            'arquivo.*.file' => 'Cada anexo deve ser um arquivo válido.',
            'arquivo.*.max' => 'Cada anexo deve ter no máximo 20 MB.',
        ];
    }

    /**
     * Garante que apenas Admin pode forçar um departamento explícito no
     * payload — agentes comuns deixam o roteamento normal cuidar disso
     * (categoria → canal → agente).
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('department_id')) {
                    return;
                }

                $user = $this->user('admin');
                $ticket = $this->route('ticket');

                $canChange = $ticket instanceof Ticket
                    ? (bool) $user?->can('changeDepartment', $ticket)
                    : (bool) $user?->isAdmin();

                if (! $canChange) {
                    $validator->errors()->add(
                        'department_id',
                        'Você não tem permissão para definir o departamento do chamado.'
                    );
                }
            },
        ];
    }

    private function resolveEffectiveStatusId(): int
    {
        $requestedStatusId = (int) $this->input('status_id', Ticket::STATUS_PENDING_ID);
        $agentId = $this->filled('agent_id') ? (int) $this->input('agent_id') : null;

        if (Status::isRequestStatus($requestedStatusId)) {
            return $requestedStatusId;
        }

        if (
            Status::requiresAgent($requestedStatusId) ||
            Status::isTerminal($requestedStatusId) ||
            Status::requiresSchedule($requestedStatusId)
        ) {
            return $requestedStatusId;
        }

        if (! $agentId) {
            return Ticket::STATUS_PENDING_ID;
        }

        if (
            in_array($requestedStatusId, TicketStatusCatalog::preserveWithAgentIds(), true) ||
            Status::isTerminal($requestedStatusId) ||
            Status::requiresSchedule($requestedStatusId)
        ) {
            return $requestedStatusId;
        }

        return Ticket::STATUS_IN_PROGRESS_ID;
    }
}
