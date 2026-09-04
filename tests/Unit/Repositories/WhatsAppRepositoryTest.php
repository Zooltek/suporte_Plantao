<?php

/**
 * Testes de integração — WhatsAppRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\DTO\WhatsApp\IncomingMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Repositories\WhatsAppRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function wa_repo(): WhatsAppRepository
{
    return new WhatsAppRepository();
}

function wa_conversation(): WhatsAppConversation
{
    return WhatsAppConversation::factory()->create();
}

function wa_incoming_message(
    string  $messageId = 'msg-default-id',
    string  $body      = 'Olá, preciso de suporte.',
    string  $type      = 'text',
    ?string $mediaUrl  = null,
    ?string $mediaId   = null,
    ?string $mimetype  = null,
): IncomingMessage {
    return new IncomingMessage(
        from:      '5527999990000',
        body:      $body,
        type:      $type,
        mediaUrl:  $mediaUrl,
        mediaId:   $mediaId,
        mimetype:  $mimetype,
        timestamp: (string) now()->timestamp,
        messageId: $messageId,
    );
}

// ─── createOutboundMessage ────────────────────────────────────────────────────

describe('WhatsAppRepository — createOutboundMessage', function () {

    it('persiste a mensagem outbound no banco', function () {
        $conversation = wa_conversation();

        $message = wa_repo()->createOutboundMessage($conversation, 'Olá! Como posso ajudar?');

        expect($message)->toBeInstanceOf(WhatsAppMessage::class);
        expect($message->exists)->toBeTrue();
        expect($message->direction)->toBe('outbound');
        expect($message->type)->toBe('text');
        expect($message->body)->toBe('Olá! Como posso ajudar?');
        expect($message->conversation_id)->toBe($conversation->id);

        test()->assertDatabaseHas('whatsapp_messages', [
            'id'              => $message->id,
            'direction'       => 'outbound',
            'conversation_id' => $conversation->id,
        ]);
    });

});

// ─── createInboundMessage ─────────────────────────────────────────────────────

describe('WhatsAppRepository — createInboundMessage', function () {

    it('persiste a mensagem inbound no banco', function () {
        $conversation = wa_conversation();
        $incoming     = wa_incoming_message(messageId: 'msg-abc-123');

        $message = wa_repo()->createInboundMessage($conversation, $incoming);

        expect($message)->toBeInstanceOf(WhatsAppMessage::class);
        expect($message->exists)->toBeTrue();
        expect($message->direction)->toBe('inbound');
        expect($message->type)->toBe('text');
        expect($message->body)->toBe('Olá, preciso de suporte.');
        expect($message->provider_message_id)->toBe('msg-abc-123');
        expect($message->conversation_id)->toBe($conversation->id);

        test()->assertDatabaseHas('whatsapp_messages', [
            'id'                  => $message->id,
            'direction'           => 'inbound',
            'provider_message_id' => 'msg-abc-123',
        ]);
    });

    it('persiste body como null quando a mensagem não tem corpo', function () {
        $conversation = wa_conversation();
        $incoming     = wa_incoming_message(body: '', type: 'image', messageId: 'msg-media-001');

        $message = wa_repo()->createInboundMessage($conversation, $incoming);

        expect($message->body)->toBeNull();
    });

});

// ─── messageAlreadyExists ─────────────────────────────────────────────────────

describe('WhatsAppRepository — messageAlreadyExists', function () {

    it('retorna true quando o provider_message_id já existe', function () {
        $conversation = wa_conversation();

        WhatsAppMessage::factory()->create([
            'conversation_id'     => $conversation->id,
            'provider_message_id' => 'duplicate-id-xyz',
        ]);

        $result = wa_repo()->messageAlreadyExists('duplicate-id-xyz');

        expect($result)->toBeTrue();
    });

    it('retorna false quando o provider_message_id não existe', function () {
        $result = wa_repo()->messageAlreadyExists('non-existent-id-999');

        expect($result)->toBeFalse();
    });

});
