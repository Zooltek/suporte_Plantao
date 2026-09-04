<?php

use App\Services\WhatsApp\Providers\GenericWhatsAppProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// ─────────────────────────────────────────────────────────────────────────────
// GenericWhatsAppProvider
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    $this->provider = new GenericWhatsAppProvider;
});

// ─────────────────────────────────────────────────────────────────────────────
// verifyWebhook
// ─────────────────────────────────────────────────────────────────────────────

describe('GenericWhatsAppProvider::verifyWebhook()', function () {

    it('aceita qualquer requisição quando webhook_secret está vazio', function () {
        config(['whatsapp.webhook_secret' => '']);

        $request = Request::create('/webhook', 'POST', [], [], [], [], 'payload');

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

    it('valida assinatura HMAC correta via X-Hub-Signature-256', function () {
        $secret = 'meu-segredo';
        $body = '{"from":"5527...","type":"text"}';
        $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

        config(['whatsapp.webhook_secret' => $secret]);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-Hub-Signature-256' => $sig,
        ], $body);

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

    it('rejeita assinatura inválida', function () {
        config(['whatsapp.webhook_secret' => 'segredo']);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-Hub-Signature-256' => 'sha256=assinatura-errada',
        ], 'body');

        expect($this->provider->verifyWebhook($request))->toBeFalse();
    });

    it('aceita assinatura via X-Webhook-Signature como fallback', function () {
        $secret = 'outro-segredo';
        $body = 'payload-teste';
        $sig = 'sha256='.hash_hmac('sha256', $body, $secret);

        config(['whatsapp.webhook_secret' => $secret]);

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'HTTP_X-Webhook-Signature' => $sig,
        ], $body);

        expect($this->provider->verifyWebhook($request))->toBeTrue();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// parseIncoming
// ─────────────────────────────────────────────────────────────────────────────

describe('GenericWhatsAppProvider::parseIncoming()', function () {

    it('parseia mensagem de texto corretamente', function () {
        $payload = [
            'from' => '5527999990000',
            'type' => 'text',
            'message_id' => 'msg-001',
            'timestamp' => '1700000000',
            'text' => ['body' => 'Olá'],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($payload));

        $msg = $this->provider->parseIncoming($request);

        expect($msg)->not->toBeNull()
            ->and($msg->from)->toBe('5527999990000')
            ->and($msg->body)->toBe('Olá')
            ->and($msg->type)->toBe('text')
            ->and($msg->messageId)->toBe('msg-001')
            ->and($msg->isText())->toBeTrue();
    });

    it('parseia mensagem de mídia corretamente', function () {
        $payload = [
            'from' => '5527999990000',
            'type' => 'image',
            'message_id' => 'msg-002',
            'timestamp' => '1700000001',
            'media' => [
                'id' => 'media-123',
                'url' => 'https://example.com/img.jpg',
                'mime_type' => 'image/jpeg',
            ],
        ];

        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $msg = $this->provider->parseIncoming($request);

        expect($msg)->not->toBeNull()
            ->and($msg->type)->toBe('image')
            ->and($msg->mediaId)->toBe('media-123')
            ->and($msg->mediaUrl)->toBe('https://example.com/img.jpg')
            ->and($msg->mimetype)->toBe('image/jpeg')
            ->and($msg->isMedia())->toBeTrue();
    });

    it('retorna null para payload de status de entrega', function () {
        $payload = ['status' => 'delivered', 'message_id' => 'msg-001'];
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        expect($this->provider->parseIncoming($request))->toBeNull();
    });

    it('retorna null para payload sem campo from', function () {
        $payload = ['type' => 'text', 'text' => ['body' => 'teste']];
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        expect($this->provider->parseIncoming($request))->toBeNull();
    });

    it('gera messageId quando não fornecido no payload', function () {
        $payload = ['from' => '5527...', 'type' => 'text', 'text' => ['body' => 'oi']];
        $request = Request::create('/webhook', 'POST', [], [], [], [], json_encode($payload));

        $msg = $this->provider->parseIncoming($request);

        expect($msg->messageId)->not->toBeEmpty();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// sendText
// ─────────────────────────────────────────────────────────────────────────────

describe('GenericWhatsAppProvider::sendText()', function () {

    it('retorna false e não faz HTTP quando api_url está vazia', function () {
        config(['whatsapp.api_url' => '', 'whatsapp.api_token' => '']);

        Http::fake();

        $result = $this->provider->sendText('5527999990000', 'Olá');

        expect($result)->toBeFalse();
        Http::assertNothingSent();
    });

    it('retorna o id da mensagem quando API responde 2xx', function () {
        config([
            'whatsapp.api_url' => 'https://api.whatsapp.test',
            'whatsapp.api_token' => 'token-test',
            'whatsapp.from_number' => '2798116205',
        ]);

        Http::fake([
            'https://api.whatsapp.test/messages' => Http::response(['id' => 'sent-001'], 200),
        ]);

        $result = $this->provider->sendText('5527999990000', 'Mensagem de teste');

        expect($result)->toBe('sent-001');
    });

    it('retorna false quando API responde erro', function () {
        config([
            'whatsapp.api_url' => 'https://api.whatsapp.test',
            'whatsapp.api_token' => 'token-test',
        ]);

        Http::fake([
            'https://api.whatsapp.test/messages' => Http::response(['error' => 'fail'], 500),
        ]);

        $result = $this->provider->sendText('5527999990000', 'Mensagem');

        expect($result)->toBeFalse();
    });

    it('retorna false e loga quando ocorre exceção HTTP', function () {
        config([
            'whatsapp.api_url' => 'https://api.whatsapp.test',
            'whatsapp.api_token' => 'token-test',
        ]);

        Http::fake(fn () => throw new \Exception('Connection refused'));

        $result = $this->provider->sendText('5527999990000', 'Mensagem');

        expect($result)->toBeFalse();
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// name
// ─────────────────────────────────────────────────────────────────────────────

describe('GenericWhatsAppProvider::name()', function () {

    it('retorna o nome do provedor', function () {
        expect($this->provider->name())->toBe('generic');
    });

});
