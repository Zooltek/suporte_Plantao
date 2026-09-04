<?php

namespace App\Console\Commands;

use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Reseta conversas do WhatsApp chatbot que estão inativas por mais de N minutos.
 *
 * - Verifica todas as conversas ativas (não terminais) com last_activity_at
 *   anterior ao limite de TTL configurado.
 * - Limpa o payload, tenta enviar mensagem de timeout ao usuário, e marca
 *   o estado como 'completed' para que novas mensagens iniciem nova sessão.
 *
 * Agendado em routes/console.php para rodar a cada minuto via Laravel Scheduler.
 */
class CheckExpiredWhatsAppConversationsCommand extends Command
{
    protected $signature = 'whatsapp:check-expired-conversations';

    protected $description = 'Reseta conversas do chatbot WhatsApp expiradas por inatividade';

    public function handle(?\App\Services\WhatsApp\WhatsAppBotReleaseService $botReleaseService = null): int
    {
        $botReleaseService ??= app(\App\Services\WhatsApp\WhatsAppBotReleaseService::class);
        $ttl = (int) config('whatsapp.chatbot.session_ttl_minutes', 60);
        $cutoff = now()->subMinutes($ttl);

        $conversations = WhatsAppConversation::query()
            ->whereNotIn('state', [
                ConversationState::COMPLETED->value,
                ConversationState::CANCELLED->value,
            ])
            ->where(function ($query) use ($cutoff) {
                $query->where(function ($q) use ($cutoff) {
                    $q->where('state', '!=', ConversationState::HUMAN_PENDING->value)
                        ->where('last_activity_at', '<', $cutoff);
                })->orWhere('state', ConversationState::HUMAN_PENDING->value);
            })
            ->cursor();

        $count = 0;
        foreach ($conversations as $conversation) {
            if ($conversation->state === ConversationState::HUMAN_PENDING) {
                if ($botReleaseService->releaseIfHumanPendingIdle($conversation)) {
                    $count++;
                }
                continue;
            }

            // Verifica novamente se ainda expirou (proteção contra race)
            if (! $conversation->isExpired()) {
                continue;
            }

            $conversation->payload = [];
            $conversation->company_id = null;
            $conversation->ticket_id = null;
            $conversation->state = ConversationState::COMPLETED;
            $conversation->completed_at = now();
            $conversation->save();

            $count++;

            Log::info('[WhatsApp] Conversa expirada e resetada.', [
                'conversation_id' => $conversation->id,
                'phone' => $conversation->phone,
                'last_activity_at' => $conversation->last_activity_at,
            ]);
        }

        $this->info("{$count} conversas expiradas foram resetadas.");

        return self::SUCCESS;
    }
}
