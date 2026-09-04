<?php

namespace App\Jobs\WhatsApp;

use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\ChatBotService;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use App\Services\WhatsApp\WhatsAppService;
use App\Support\Phone\PhoneNumber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Processa uma mensagem WhatsApp recebida de forma assíncrona.
 *
 * - Garante idempotência (mensagem já processada → ignorada)
 * - Resolve ou cria a sessão de conversa do remetente
 * - Delega ao ChatBotService para transição de estado
 * - Máximo 3 tentativas com backoff exponencial
 */
class ProcessIncomingMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 60;

    public function __construct(
        private readonly IncomingMessage $message,
    ) {}

    public function handle(
        WhatsAppService $whatsAppService,
        ChatBotService  $chatBotService,
        ?WhatsAppBotReleaseService $botReleaseService = null,
    ): void {
        $botReleaseService ??= app(WhatsAppBotReleaseService::class);

        // Idempotência: mensagem já processada?
        if ($whatsAppService->alreadyProcessed($this->message->messageId)) {
            Log::debug('[WhatsApp Job] Mensagem já processada, ignorando.', [
                'messageId' => $this->message->messageId,
            ]);
            return;
        }

        $conversation = $this->resolveConversation($this->message->from, $botReleaseService);

        // Registrar mensagem inbound no histórico
        $whatsAppService->recordInbound($conversation, $this->message);

        // Delegar ao chatbot
        $chatBotService->handle($conversation, $this->message);
    }

    public function backoff(): array
    {
        return [10, 30, 60]; // Segundos entre tentativas
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('[WhatsApp Job] Falha ao processar mensagem.', [
            'from'      => $this->message->from,
            'messageId' => $this->message->messageId,
            'error'     => $exception->getMessage(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve a conversa ativa do remetente, ou cria uma nova.
     * Uma nova sessão é criada quando:
     *  - Não existe conversa para o número
     *  - A conversa existente está em estado terminal (COMPLETED/CANCELLED)
     *  - A conversa existente está expirada
     */
    private function resolveConversation(
        string $phone,
        WhatsAppBotReleaseService $botReleaseService,
    ): WhatsAppConversation
    {
        $normalizedPhone = PhoneNumber::normalize($phone);
        $phone = $normalizedPhone !== '' ? $normalizedPhone : $phone;

        $existing = WhatsAppConversation::where('phone', $phone)
            ->latest()
            ->first();

        // Se a conversa existente estiver dentro da janela de delay pós-encerramento do chamado,
        // garantimos que o bot permaneça em silêncio (HUMAN_PENDING) e não crie nova sessão.
        if ($existing && $botReleaseService->isWithinTicketClosedDelay($existing)) {
            if ($existing->state !== ConversationState::HUMAN_PENDING) {
                $existing->forceFill(['state' => ConversationState::HUMAN_PENDING])->save();
            }

            return $existing;
        }

        if ($existing && $botReleaseService->releaseIfHumanPendingIdle($existing)) {
            $existing = null;
        }

        if ($existing && $existing->isActive() && !$existing->isExpired()) {
            return $existing;
        }

        return WhatsAppConversation::create([
            'phone'            => $phone,
            'state'            => ConversationState::GREETING,
            'payload'          => [],
            'last_activity_at' => now(),
        ]);
    }
}
