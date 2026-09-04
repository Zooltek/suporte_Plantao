<?php

namespace App\Http\Requests\Agent\Tickets;

use App\Models\Ticket\Ticket;
use App\Support\Phone\PhoneNumber;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida o início de uma conversa de WhatsApp a partir do detalhe do chamado.
 * Autorização reaproveita a TicketPolicy: só quem pode responder o chamado
 * pode iniciar o contato pelo WhatsApp.
 */
class StartWhatsAppConversationRequest extends FormRequest
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
        return [
            'phone' => ['required', 'string', 'regex:/^\d{10,15}$/'],
            'message' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => 'Informe o número de WhatsApp do cliente.',
            'phone.regex' => 'Informe um número de WhatsApp válido (com DDD).',
            'message.required' => 'Digite a mensagem que será enviada ao cliente.',
            'message.max' => 'A mensagem não pode passar de 5000 caracteres.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => PhoneNumber::normalize($this->input('phone')),
            'message' => trim((string) $this->input('message')),
        ]);
    }

    /**
     * Mantém o agente na aba WhatsApp ao retornar com erros de validação.
     */
    protected function getRedirectUrl(): string
    {
        $ticket = $this->route('ticket');

        return route('agent.ticket.show', ['ticket' => $ticket->id, 'tab' => 'whatsapp']);
    }
}
