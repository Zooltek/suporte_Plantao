<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\DTO\WhatsApp\IncomingMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;

interface WhatsAppRepositoryInterface
{
    /**
     * Persiste uma mensagem outbound para uma conversa.
     */
    public function createOutboundMessage(
        WhatsAppConversation $conversation,
        string $body,
        ?int $userId = null,
        bool $internal = false,
        ?string $providerMessageId = null,
    ): WhatsAppMessage;

    /**
     * Persiste uma mensagem inbound recebida via webhook.
     */
    public function createInboundMessage(
        WhatsAppConversation $conversation,
        IncomingMessage $message,
        ?string $attachmentPath = null,
        ?string $originalFilename = null,
    ): WhatsAppMessage;

    /**
     * Verifica se uma mensagem com o provider_message_id já foi processada.
     */
    public function messageAlreadyExists(string $providerMessageId): bool;

    /**
     * Localiza a mensagem inbound persistida pelo provider_message_id.
     */
    public function findInboundByProviderMessageId(string $providerMessageId): ?WhatsAppMessage;
}
