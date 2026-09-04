<?php

use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;

// ─────────────────────────────────────────────────────────────────────────────
// WhatsAppMessage Model
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppMessage::isInbound()', function () {

    it('retorna true para mensagem inbound', function () {
        $msg = WhatsAppMessage::factory()->inbound()->create();
        expect($msg->isInbound())->toBeTrue();
    });

    it('retorna false para mensagem outbound', function () {
        $msg = WhatsAppMessage::factory()->outbound()->create();
        expect($msg->isInbound())->toBeFalse();
    });

});

describe('WhatsAppMessage::isMedia()', function () {

    it('retorna false para mensagem de texto', function () {
        $msg = WhatsAppMessage::factory()->create(['type' => 'text']);
        expect($msg->isMedia())->toBeFalse();
    });

    it('retorna true para todos os tipos de mídia', function (string $type) {
        $msg = WhatsAppMessage::factory()->create(['type' => $type]);
        expect($msg->isMedia())->toBeTrue();
    })->with(['image', 'document', 'audio', 'video']);

});

describe('WhatsAppMessage relacionamentos', function () {

    it('pertence a uma conversa', function () {
        $conv = WhatsAppConversation::factory()->create();
        $msg  = WhatsAppMessage::factory()->create(['conversation_id' => $conv->id]);

        expect($msg->conversation)->toBeInstanceOf(WhatsAppConversation::class)
            ->and($msg->conversation->id)->toBe($conv->id);
    });

});

describe('WhatsAppMessage criação via factory', function () {

    it('cria mensagem de imagem com campos corretos', function () {
        $msg = WhatsAppMessage::factory()->image()->create();

        expect($msg->type)->toBe('image')
            ->and($msg->attachment_path)->toContain('.jpg')
            ->and($msg->mime_type)->toBe('image/jpeg')
            ->and($msg->file_size)->toBeGreaterThan(0);
    });

    it('cria mensagem de documento com campos corretos', function () {
        $msg = WhatsAppMessage::factory()->document()->create();

        expect($msg->type)->toBe('document')
            ->and($msg->attachment_path)->toContain('.pdf')
            ->and($msg->mime_type)->toBe('application/pdf');
    });

});
