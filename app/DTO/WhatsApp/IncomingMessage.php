<?php

namespace App\DTO\WhatsApp;

/**
 * Representa uma mensagem recebida pelo webhook do provedor WhatsApp.
 * Abstrai diferenças entre provedores (Twilio, Zenvia, Gupshup, etc.).
 */
final readonly class IncomingMessage
{
    public function __construct(
        public string $from,       // Número do remetente (ex: 5527999999999)
        public string $body,       // Texto da mensagem (vazio para mídias)
        public string $type,       // text | image | document | audio | video
        public ?string $mediaUrl,   // URL pública da mídia (quando type != text)
        public ?string $mediaId,    // ID interno da mídia no provedor
        public ?string $mimetype,   // MIME type da mídia
        public string $timestamp,  // Timestamp Unix da mensagem
        public string $messageId,  // ID único da mensagem no provedor
        public ?string $fileName = null, // Nome original do arquivo enviado (quando o provedor fornece)
    ) {}

    public function isText(): bool
    {
        return $this->type === 'text';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'document', 'audio', 'video'], true);
    }

    public function normalizedBody(): string
    {
        return mb_strtolower(trim($this->body));
    }
}
