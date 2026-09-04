<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\WhatsAppRepositoryInterface;
use App\DTO\WhatsApp\IncomingMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;

class WhatsAppRepository implements WhatsAppRepositoryInterface
{
    public function createOutboundMessage(
        WhatsAppConversation $conversation,
        string $body,
        ?int $userId = null,
        bool $internal = false,
        ?string $providerMessageId = null,
    ): WhatsAppMessage {
        return WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $body,
            'is_internal' => $internal,
            'provider_message_id' => $providerMessageId,
        ]);
    }

    public function createInboundMessage(
        WhatsAppConversation $conversation,
        IncomingMessage $message,
        ?string $attachmentPath = null,
        ?string $originalFilename = null,
    ): WhatsAppMessage {
        return WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'direction' => 'inbound',
            'type' => $message->type,
            'body' => $message->body ?: null,
            'attachment_path' => $attachmentPath,
            'original_filename' => $originalFilename ?: $message->fileName,
            'mime_type' => $message->mimetype,
            'provider_message_id' => $message->messageId,
        ]);
    }

    public function messageAlreadyExists(string $providerMessageId): bool
    {
        return WhatsAppMessage::where('provider_message_id', $providerMessageId)
            ->where('direction', 'inbound')
            ->exists();
    }

    public function messageAlreadyExistsWithBody(string $providerMessageId, string $body): bool
    {
        return WhatsAppMessage::where('provider_message_id', $providerMessageId)
            ->where('direction', 'inbound')
            ->where('body', $body)
            ->exists();
    }

    public function findInboundByProviderMessageId(string $providerMessageId): ?WhatsAppMessage
    {
        return WhatsAppMessage::where('provider_message_id', $providerMessageId)
            ->where('direction', 'inbound')
            ->latest('id')
            ->first();
    }
}
