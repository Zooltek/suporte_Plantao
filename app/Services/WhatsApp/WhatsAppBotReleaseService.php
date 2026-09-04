<?php

namespace App\Services\WhatsApp;

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Support\Facades\Log;

class WhatsAppBotReleaseService
{
    public function __construct(
        private readonly WhatsAppService $whatsApp,
        private readonly WhatsAppBotMessageService $botMessages,
    ) {}

    public function releaseTicketConversations(Ticket $ticket, string $reason = 'ticket_closed'): int
    {
        $released = 0;
        $delayMinutes = $this->ticketClosedDelayMinutes();

        WhatsAppConversation::query()
            ->where('ticket_id', $ticket->id)
            ->where('state', ConversationState::HUMAN_PENDING)
            ->each(function (WhatsAppConversation $conversation) use ($reason, $delayMinutes, &$released): void {
                if ($conversation->getPayloadValue('ticket_closed_at') && $conversation->getPayloadValue('bot_release_after')) {
                    return;
                }

                $this->notifyConversationFinished($conversation);

                $payload = $conversation->payload ?? [];
                $payload['ticket_closed_at'] = now()->toIso8601String();
                $payload['ticket_closed_reason'] = $reason;

                if ($delayMinutes <= 0) {
                    $payload['bot_auto_released_at'] = now()->toIso8601String();
                    $payload['bot_auto_release_reason'] = $reason;

                    $conversation->forceFill([
                        'state' => ConversationState::COMPLETED,
                        'payload' => $payload,
                        'completed_at' => $conversation->completed_at ?? now(),
                    ])->save();

                    Log::info('[WhatsApp ChatBot] Bot liberado automaticamente no encerramento (delay=0).', [
                        'conversation' => $conversation->id,
                        'ticket_id' => $conversation->ticket_id,
                        'reason' => $reason,
                    ]);
                } else {
                    $payload['bot_release_after'] = now()->addMinutes($delayMinutes)->toIso8601String();

                    $conversation->forceFill([
                        'state' => ConversationState::HUMAN_PENDING,
                        'payload' => $payload,
                        'last_activity_at' => now(),
                    ])->save();

                    Log::info('[WhatsApp ChatBot] Encerramento de chamado registrado com delay para liberação do bot.', [
                        'conversation' => $conversation->id,
                        'ticket_id' => $conversation->ticket_id,
                        'delay_minutes' => $delayMinutes,
                        'release_after' => $payload['bot_release_after'],
                    ]);
                }

                $released++;
            });

        return $released;
    }

    public function releaseIfHumanPendingIdle(WhatsAppConversation $conversation): bool
    {
        if (! $this->isHumanPendingIdle($conversation)) {
            return false;
        }

        $reason = $conversation->getPayloadValue('bot_release_after')
            ? 'ticket_closed_delay_expired'
            : 'human_handoff_idle';

        $this->completeHumanPending($conversation, $reason);

        return true;
    }

    /**
     * Libera a conversa de volta ao bot (ação manual do agente/admin).
     * Envia a mensagem de finalização ao cliente e reseta o estado para GREETING,
     * de modo que a próxima mensagem reapresente o menu do bot.
     */
    public function releaseManually(WhatsAppConversation $conversation): bool
    {
        if ($conversation->state !== ConversationState::HUMAN_PENDING) {
            return false;
        }

        $this->notifyConversationFinished($conversation);

        $conversation->state = ConversationState::GREETING;
        $conversation->last_activity_at = now();
        $conversation->save();

        return true;
    }

    /**
     * Pausa o bot e coloca a conversa em atendimento humano (ação manual do agente/admin).
     */
    public function pauseManually(WhatsAppConversation $conversation): bool
    {
        if ($conversation->state === ConversationState::HUMAN_PENDING) {
            return false;
        }

        $payload = $conversation->payload ?? [];
        unset(
            $payload['bot_release_after'],
            $payload['bot_auto_released_at'],
            $payload['bot_auto_release_reason'],
            $payload['ticket_closed_at'],
            $payload['ticket_closed_reason']
        );

        $conversation->state = ConversationState::HUMAN_PENDING;
        $conversation->payload = $payload;
        $conversation->last_activity_at = now();
        $conversation->save();

        Log::info('[WhatsApp ChatBot] Bot pausado manualmente pelo atendente/admin.', [
            'conversation' => $conversation->id,
            'ticket_id' => $conversation->ticket_id,
        ]);

        return true;
    }

    public function isHumanPendingIdle(WhatsAppConversation $conversation): bool
    {
        if ($conversation->state !== ConversationState::HUMAN_PENDING) {
            return false;
        }

        // Se ainda está dentro do período de delay pós-fechamento do chamado, nunca está idle
        if ($this->isWithinTicketClosedDelay($conversation)) {
            return false;
        }

        // Conversas vinculadas a um chamado
        if ($conversation->ticket_id) {
            $ticket = $conversation->ticket ?? Ticket::find($conversation->ticket_id);
            if ($ticket && ! $this->isTicketClosed($ticket)) {
                return false;
            }

            // Chamado fechado e delay expirado (ou delay é 0): libera
            return true;
        }

        if (! $conversation->last_activity_at) {
            return true;
        }

        return $conversation->last_activity_at->lte(
            now()->subMinutes($this->humanHandoffIdleMinutes())
        );
    }

    /**
     * Verifica se uma conversa está dentro da janela de delay pós-encerramento do chamado.
     * Durante essa janela, qualquer resposta do cliente não deve reativar o bot.
     */
    public function isWithinTicketClosedDelay(WhatsAppConversation $conversation): bool
    {
        $delayMinutes = $this->ticketClosedDelayMinutes();
        if ($delayMinutes <= 0) {
            return false;
        }

        $botReleaseAfter = $conversation->getPayloadValue('bot_release_after');
        if ($botReleaseAfter) {
            try {
                $releaseAt = \Illuminate\Support\Carbon::parse($botReleaseAfter);
                if (now()->lt($releaseAt)) {
                    return true;
                }
            } catch (\Throwable) {
                // segue para fallback
            }
        }

        if ($conversation->ticket_id) {
            $ticket = $conversation->ticket ?? Ticket::find($conversation->ticket_id);
            if ($ticket && $this->isTicketClosed($ticket)) {
                $closedAt = $conversation->getPayloadValue('ticket_closed_at')
                    ? \Illuminate\Support\Carbon::parse($conversation->getPayloadValue('ticket_closed_at'))
                    : ($ticket->completed_at ?? $ticket->updated_at);

                if ($closedAt && now()->lt($closedAt->copy()->addMinutes($delayMinutes))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isTicketClosed(Ticket $ticket): bool
    {
        if ($ticket->completed_at !== null) {
            return true;
        }

        return \App\Models\Ticket\Status::isTerminal($ticket->status_id);
    }

    public function humanHandoffIdleMinutes(): int
    {
        return max(1, (int) config('whatsapp.chatbot.human_handoff_idle_minutes', 5));
    }

    public function ticketClosedDelayMinutes(): int
    {
        $setting = $this->botMessages->setting('ticket_closed_delay_minutes', '');

        if ($setting !== '' && is_numeric($setting)) {
            return max(0, (int) $setting);
        }

        return max(0, (int) config('whatsapp.chatbot.ticket_closed_delay_minutes', 10));
    }

    private function completeHumanPending(WhatsAppConversation $conversation, string $reason): void
    {
        if (! $conversation->getPayloadValue('ticket_closed_at')) {
            $this->notifyConversationFinished($conversation);
        }

        $payload = $conversation->payload ?? [];
        $payload['bot_auto_released_at'] = now()->toIso8601String();
        $payload['bot_auto_release_reason'] = $reason;

        $conversation->forceFill([
            'state' => ConversationState::COMPLETED,
            'payload' => $payload,
            'completed_at' => $conversation->completed_at ?? now(),
        ])->save();

        Log::info('[WhatsApp ChatBot] Bot liberado automaticamente.', [
            'conversation' => $conversation->id,
            'ticket_id' => $conversation->ticket_id,
            'reason' => $reason,
        ]);
    }

    private function notifyConversationFinished(WhatsAppConversation $conversation): void
    {
        $text = trim($this->botMessages->message('conversation_finished'));

        if ($text === '') {
            return;
        }

        try {
            $this->whatsApp->send($conversation, $text);
        } catch (\Throwable $e) {
            Log::warning('[WhatsApp ChatBot] Falha ao enviar mensagem de finalização.', [
                'conversation' => $conversation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
