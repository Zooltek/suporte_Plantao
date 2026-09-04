<?php

namespace App\Http\Requests\Agent\Tickets;

use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CloseTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        if (! $ticket instanceof Ticket) {
            return false;
        }

        return (bool) $this->user('admin')?->can('update', $ticket);
    }

    public function rules(): array
    {
        $statusId = (int) $this->input('status_id');

        $rules = [
            'status_id' => ['required', 'integer', 'exists:ticketit_statuses,id'],
            'solution' => ['nullable', 'string'],
        ];

        if ($statusId && Status::requiresSolution($statusId)) {
            $rules['solution'] = ['required', 'string'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $statusId = (int) $this->input('status_id');

                if ($statusId && ! Status::isTerminal($statusId)) {
                    $validator->errors()->add('status_id', 'Selecione um status de encerramento válido.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'status_id.required' => 'Selecione como deseja encerrar o chamado.',
            'status_id.exists' => 'Selecione um status de encerramento válido.',
            'solution.required' => 'Informe a solução aplicada para encerrar como Resolvido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('solution')) {
            $this->merge([
                'solution' => trim((string) $this->input('solution')),
            ]);
        }
    }
}
