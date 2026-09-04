<?php

use App\Contracts\Repositories\WhatsAppRepositoryInterface;
use App\Contracts\WhatsApp\WhatsAppProviderContract;
use App\DTO\WhatsApp\IncomingMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Facades\Storage;

// ─────────────────────────────────────────────────────────────────────────────
// WhatsAppService
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->provider = $this->mock(WhatsAppProviderContract::class);
    $this->service  = new WhatsAppService($this->provider, app(WhatsAppRepositoryInterface::class));

    Storage::fake('public');
    config(['whatsapp.enabled' => true]);
});

// ─────────────────────────────────────────────────────────────────────────────
// send
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppService::send()', function () {

    it('chama provider sendText e cria mensagem outbound', function () {
        $conv = WhatsAppConversation::factory()->create();

        $this->provider->shouldReceive('sendText')
            ->once()
            ->with($conv->phone, 'Olá, tudo bem?')
            ->andReturn(true);

        $this->service->send($conv, 'Olá, tudo bem?');

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conv->id,
            'direction'       => 'outbound',
            'type'            => 'text',
            'body'            => 'Olá, tudo bem?',
        ]);
    });

    it('registra mensagem mesmo quando provider retorna false', function () {
        $conv = WhatsAppConversation::factory()->create();

        $this->provider->shouldReceive('sendText')->andReturn(false);

        $this->service->send($conv, 'Mensagem com falha');

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id' => $conv->id,
            'direction'       => 'outbound',
        ]);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// recordInbound
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppService::recordInbound()', function () {

    it('persiste mensagem inbound no banco', function () {
        $conv = WhatsAppConversation::factory()->create();
        $dto  = new IncomingMessage(
            from:      $conv->phone,
            body:      'ajuda',
            type:      'text',
            mediaUrl:  null,
            mediaId:   null,
            mimetype:  null,
            timestamp: '1700000000',
            messageId: 'msg-inbound-001',
        );

        $msg = $this->service->recordInbound($conv, $dto);

        expect($msg)->toBeInstanceOf(WhatsAppMessage::class)
            ->and($msg->direction)->toBe('inbound')
            ->and($msg->body)->toBe('ajuda')
            ->and($msg->provider_message_id)->toBe('msg-inbound-001');

        $this->assertDatabaseHas('whatsapp_messages', [
            'conversation_id'     => $conv->id,
            'direction'           => 'inbound',
            'provider_message_id' => 'msg-inbound-001',
        ]);
    });

    it('salva body como null para mensagens de mídia', function () {
        $conv = WhatsAppConversation::factory()->create();
        $dto  = new IncomingMessage(
            from:      $conv->phone,
            body:      '',
            type:      'image',
            mediaUrl:  'https://example.com/img.jpg',
            mediaId:   'media-001',
            mimetype:  'image/jpeg',
            timestamp: '1700000000',
            messageId: 'msg-media-001',
        );

        $msg = $this->service->recordInbound($conv, $dto);

        expect($msg->body)->toBeNull()
            ->and($msg->type)->toBe('image');
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// downloadAndStoreMedia
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppService::downloadAndStoreMedia()', function () {

    it('retorna null para mensagem de texto', function () {
        $dto = new IncomingMessage('5527...', 'texto', 'text', null, null, null, '0', 'id');

        expect($this->service->downloadAndStoreMedia($dto))->toBeNull();
    });

    it('retorna null para mídia sem mediaId e sem mediaUrl', function () {
        $dto = new IncomingMessage('5527...', '', 'image', null, null, null, '0', 'id');

        expect($this->service->downloadAndStoreMedia($dto))->toBeNull();
    });

    it('baixa e armazena mídia retornando o path', function () {
        $dto = new IncomingMessage(
            from:      '5527999990000',
            body:      '',
            type:      'image',
            mediaUrl:  'https://example.com/img.jpg',
            mediaId:   'media-abc',
            mimetype:  'image/jpeg',
            timestamp: '0',
            messageId: 'id',
        );

        $this->provider->shouldReceive('downloadMedia')
            ->once()
            ->with('media-abc', 'https://example.com/img.jpg')
            ->andReturn('fake-binary-content');

        $path = $this->service->downloadAndStoreMedia($dto);

        expect($path)->not->toBeNull()
            ->and($path)->toContain('whatsapp/attachments/')
            ->and($path)->toContain('.jpg');

        Storage::disk('public')->assertExists($path);
    });

    it('infere extensão correta para diferentes MIME types', function (string $mime, string $ext) {
        $dto = new IncomingMessage('5527...', '', 'document', null, 'media-id', $mime, '0', 'id');

        $this->provider->shouldReceive('downloadMedia')->andReturn('binary');

        $path = $this->service->downloadAndStoreMedia($dto);

        expect($path)->toContain('.' . $ext);
    })->with([
        ['image/jpeg',       'jpg'],
        ['image/png',        'png'],
        ['application/pdf',  'pdf'],
        ['video/mp4',        'mp4'],
        ['audio/ogg',        'ogg'],
        ['image/webp',       'webp'],
        ['application/xml',  'xml'],
        ['text/xml',         'xml'],
        ['application/x-pkcs12', 'pfx'],
        ['application/octet-stream', 'bin'],
    ]);

    it('preserva a extensão do fileName quando o provedor fornece (Bug 3)', function () {
        // O cliente envia NFe-123.xml; o navegador do agente precisa abrir como XML,
        // não como application/octet-stream (.bin).
        $dto = new IncomingMessage(
            from:      '5527...',
            body:      '',
            type:      'document',
            mediaUrl:  null,
            mediaId:   'media-xml',
            mimetype:  'application/octet-stream', // mime sem informação útil
            timestamp: '0',
            messageId: 'id-xml',
            fileName:  'NFe-12345.xml',
        );

        $this->provider->shouldReceive('downloadMedia')->andReturn('<xml></xml>');

        $path = $this->service->downloadAndStoreMedia($dto);

        expect($path)->toContain('.xml');
    });

    it('persiste original_filename ao registrar mensagem inbound com mídia (Bug 3)', function () {
        $conv = WhatsAppConversation::factory()->create();
        $dto  = new IncomingMessage(
            from:      $conv->phone,
            body:      '',
            type:      'document',
            mediaUrl:  null,
            mediaId:   'media-nfe',
            mimetype:  'application/xml',
            timestamp: '0',
            messageId: 'msg-nfe-001',
            fileName:  'NFe-99887.xml',
        );

        $this->provider->shouldReceive('downloadMedia')->andReturn('<xml></xml>');

        $message = $this->service->recordInbound($conv, $dto);

        expect($message->original_filename)->toBe('NFe-99887.xml');
    });

    it('retorna null quando provider lança exceção no download', function () {
        $dto = new IncomingMessage('5527...', '', 'image', null, 'media-id', 'image/jpeg', '0', 'id');

        $this->provider->shouldReceive('downloadMedia')
            ->andThrow(new \RuntimeException('Download failed'));

        expect($this->service->downloadAndStoreMedia($dto))->toBeNull();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// alreadyProcessed
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppService::alreadyProcessed()', function () {

    it('retorna false quando messageId não existe', function () {
        expect($this->service->alreadyProcessed('msg-inexistente'))->toBeFalse();
    });

    it('retorna true quando messageId já existe', function () {
        WhatsAppMessage::factory()->create(['provider_message_id' => 'msg-duplicado']);

        expect($this->service->alreadyProcessed('msg-duplicado'))->toBeTrue();
    });

});
