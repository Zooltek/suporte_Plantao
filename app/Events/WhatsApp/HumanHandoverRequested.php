<?php

namespace App\Events\WhatsApp;

use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Disparado quando um cliente solicita atendimento humano via chatbot.
 *
 * Ouvintes devem notificar os agentes disponíveis (painel, e-mail, etc.)
 * e registrar o handover para auditoria.
 */
class HumanHandoverRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WhatsAppConversation $conversation,
    ) {}
}
