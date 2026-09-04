<?php

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Support\Phone\PhoneNumber;
use Illuminate\Validation\ValidationException;

/**
 * Inicia uma conversa de WhatsApp pelo lado do agente (outbound), para
 * chamados que ainda não possuem conversa vinculada.
 *
 * A conversa nasce em HUMAN_PENDING: o chatbot permanece em silêncio e o
 * agente mantém o controle até liberar o bot manualmente. Quando o cliente
 * responder, o ProcessIncomingMessageJob reaproveita esta mesma conversa.
 */
class WhatsAppConversationStarterService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly AgentMessageSignature $signature,
    ) {}

    /**
     * Cria a conversa vinculada ao chamado e envia a primeira mensagem.
     *
     * @throws ValidationException quando o chamado já tem conversa, o número
     *                             é inválido, já há conversa ativa para o
     *                             número ou o número não existe no WhatsApp.
     */
    public function startForTicket(
        Ticket $ticket,
        User $user,
        string $phone,
        string $message,
    ): WhatsAppConversation {
        if (WhatsAppConversation::query()->where('ticket_id', $ticket->id)->exists()) {
            throw ValidationException::withMessages([
                'phone' => 'Este chamado já possui uma conversa WhatsApp vinculada.',
            ]);
        }

        $normalizedPhone = PhoneNumber::normalize($phone);

        if ($normalizedPhone === '') {
            throw ValidationException::withMessages([
                'phone' => 'Informe um número de WhatsApp válido.',
            ]);
        }

        $this->guardActiveConversation($normalizedPhone);

        if (! $this->whatsApp->numberExists($normalizedPhone)) {
            throw ValidationException::withMessages([
                'phone' => 'Este número não possui uma conta ativa no WhatsApp.',
            ]);
        }

        $conversation = WhatsAppConversation::query()->create([
            'phone' => $normalizedPhone,
            'state' => ConversationState::HUMAN_PENDING,
            'payload' => [],
            'company_id' => $ticket->company_id,
            'ticket_id' => $ticket->id,
            'last_activity_at' => now(),
        ]);

        $this->whatsApp->send($conversation, $message, $user->id, false, $this->signature->apply($message, $user));
        $conversation->forceFill(['last_activity_at' => now()])->save();

        return $conversation;
    }

    /**
     * Impede iniciar uma conversa quando já existe uma sessão ativa e não
     * expirada para o mesmo número — evita duplicidade e cruzamento de
     * atendimentos entre chamados distintos.
     */
    private function guardActiveConversation(string $phone): void
    {
        $existing = WhatsAppConversation::query()
            ->where('phone', $phone)
            ->latest('id')
            ->first();

        if ($existing && $existing->isActive() && ! $existing->isExpired()) {
            $reference = $existing->ticket_id
                ? " (chamado #{$existing->ticket_id})."
                : '.';

            throw ValidationException::withMessages([
                'phone' => 'Já existe uma conversa ativa para este número'.$reference,
            ]);
        }
    }
}
