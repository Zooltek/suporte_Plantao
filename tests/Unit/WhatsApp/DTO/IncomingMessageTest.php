<?php

use App\DTO\WhatsApp\IncomingMessage;

// ─────────────────────────────────────────────────────────────────────────────
// IncomingMessage DTO
// ─────────────────────────────────────────────────────────────────────────────

function makeTextMessage(string $body = 'olá'): IncomingMessage
{
    return new IncomingMessage(
        from:      '5527999990000',
        body:      $body,
        type:      'text',
        mediaUrl:  null,
        mediaId:   null,
        mimetype:  null,
        timestamp: '1700000000',
        messageId: 'msg-001',
    );
}

function makeMediaMessage(string $type = 'image'): IncomingMessage
{
    return new IncomingMessage(
        from:      '5527999990000',
        body:      '',
        type:      $type,
        mediaUrl:  'https://example.com/media/img.jpg',
        mediaId:   'media-abc123',
        mimetype:  'image/jpeg',
        timestamp: '1700000000',
        messageId: 'msg-002',
    );
}

describe('IncomingMessage::isText()', function () {

    it('retorna true para mensagem de texto', function () {
        expect(makeTextMessage()->isText())->toBeTrue();
    });

    it('retorna false para mensagem de mídia', function () {
        expect(makeMediaMessage('image')->isText())->toBeFalse();
    });

});

describe('IncomingMessage::isMedia()', function () {

    it('retorna false para mensagem de texto', function () {
        expect(makeTextMessage()->isMedia())->toBeFalse();
    });

    it('retorna true para todos os tipos de mídia', function (string $type) {
        $msg = new IncomingMessage('5527...', '', $type, null, null, null, '0', 'id');
        expect($msg->isMedia())->toBeTrue();
    })->with(['image', 'document', 'audio', 'video']);

});

describe('IncomingMessage::normalizedBody()', function () {

    it('converte para minúsculas e remove espaços', function () {
        $msg = makeTextMessage('  CONFIRMAR  ');
        expect($msg->normalizedBody())->toBe('confirmar');
    });

    it('trata string vazia', function () {
        expect(makeTextMessage('')->normalizedBody())->toBe('');
    });

    it('normaliza acentos e caracteres especiais mantendo-os', function () {
        $msg = makeTextMessage('  Não  ');
        expect($msg->normalizedBody())->toBe('não');
    });

});

describe('IncomingMessage propriedades readonly', function () {

    it('expõe todas as propriedades corretamente', function () {
        $msg = new IncomingMessage(
            from:      '5527111111111',
            body:      'teste',
            type:      'text',
            mediaUrl:  'http://example.com/img.jpg',
            mediaId:   'media-xyz',
            mimetype:  'image/png',
            timestamp: '1700001000',
            messageId: 'unique-msg-id',
        );

        expect($msg->from)->toBe('5527111111111')
            ->and($msg->body)->toBe('teste')
            ->and($msg->type)->toBe('text')
            ->and($msg->mediaUrl)->toBe('http://example.com/img.jpg')
            ->and($msg->mediaId)->toBe('media-xyz')
            ->and($msg->mimetype)->toBe('image/png')
            ->and($msg->timestamp)->toBe('1700001000')
            ->and($msg->messageId)->toBe('unique-msg-id');
    });

});
