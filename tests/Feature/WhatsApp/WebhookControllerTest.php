<?php

use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use Illuminate\Support\Facades\Bus;

// ─────────────────────────────────────────────────────────────────────────────
// WebhookController — Feature Tests
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    config([
        'whatsapp.webhook_secret' => 'test-webhook-secret',
        'whatsapp.provider'       => 'generic',
    ]);
    Bus::fake();
});

// ─────────────────────────────────────────────────────────────────────────────
// GET /api/webhook/whatsapp — verificação de endpoint (challenge)
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /api/webhook/whatsapp — verify', function () {

    it('retorna o challenge com token válido', function () {
        $this->get('/api/webhook/whatsapp?' . http_build_query([
            'hub_challenge'    => 'challenge-abc123',
            'hub_verify_token' => 'test-webhook-secret',
        ]))
        ->assertStatus(200)
        ->assertSee('challenge-abc123');
    });

    it('retorna 403 com token inválido', function () {
        $this->get('/api/webhook/whatsapp?' . http_build_query([
            'hub_challenge'    => 'challenge-abc123',
            'hub_verify_token' => 'token-errado',
        ]))
        ->assertStatus(403);
    });

    it('retorna 403 quando token não é fornecido', function () {
        $this->get('/api/webhook/whatsapp?hub_challenge=abc')
            ->assertStatus(403);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// POST /api/webhook/whatsapp — recebimento de mensagens
// ─────────────────────────────────────────────────────────────────────────────

describe('POST /api/webhook/whatsapp — receive', function () {

    it('retorna 401 quando assinatura é inválida', function () {
        $this->postJson('/api/webhook/whatsapp', ['from' => '5527...'], [
            'X-Hub-Signature-256' => 'sha256=assinatura-invalida',
        ])->assertStatus(401);

        Bus::assertNothingDispatched();
    });

    it('retorna 200 ignored quando payload não contém mensagem (status de entrega)', function () {
        config(['whatsapp.webhook_secret' => '']);

        $payload = ['status' => 'delivered', 'message_id' => 'msg-001'];

        $this->postJson('/api/webhook/whatsapp', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'ignored']);

        Bus::assertNothingDispatched();
    });

    it('enfileira job e retorna 200 queued para mensagem de texto válida', function () {
        config(['whatsapp.webhook_secret' => '']);

        $payload = [
            'from'       => '5527999990000',
            'type'       => 'text',
            'message_id' => 'msg-feature-001',
            'timestamp'  => (string) time(),
            'text'       => ['body' => 'Preciso de suporte'],
        ];

        $this->postJson('/api/webhook/whatsapp', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'queued']);

        Bus::assertDispatched(ProcessIncomingMessageJob::class);
    });

    it('enfileira job para mensagem de mídia', function () {
        config(['whatsapp.webhook_secret' => '']);

        $payload = [
            'from'       => '5527999990000',
            'type'       => 'image',
            'message_id' => 'msg-media-001',
            'timestamp'  => (string) time(),
            'media'      => [
                'id'        => 'media-abc',
                'url'       => 'https://example.com/img.jpg',
                'mime_type' => 'image/jpeg',
            ],
        ];

        $this->postJson('/api/webhook/whatsapp', $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'queued']);

        Bus::assertDispatched(ProcessIncomingMessageJob::class);
    });

    it('valida assinatura HMAC correta', function () {
        $secret  = 'test-webhook-secret';
        $payload = json_encode([
            'from'       => '5527999990000',
            'type'       => 'text',
            'message_id' => 'msg-signed-001',
            'timestamp'  => '1700000000',
            'text'       => ['body' => 'mensagem assinada'],
        ]);

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->call('POST', '/api/webhook/whatsapp', [], [], [], [
                'CONTENT_TYPE'              => 'application/json',
                'HTTP_ACCEPT'               => 'application/json',
                'HTTP_X_HUB_SIGNATURE_256'  => $signature,
            ], $payload)
            ->assertStatus(200)
            ->assertJson(['status' => 'queued']);

        Bus::assertDispatched(ProcessIncomingMessageJob::class);
    });

    it('aplica rate limiting (throttle configurado)', function () {
        config(['whatsapp.webhook_secret' => '']);

        // O rate limit é 60/minuto — apenas verifica que rota existe
        $payload = [
            'from'       => '5527000000000',
            'type'       => 'text',
            'message_id' => 'msg-throttle-' . uniqid(),
            'timestamp'  => (string) time(),
            'text'       => ['body' => 'teste throttle'],
        ];

        $this->postJson('/api/webhook/whatsapp', $payload)
            ->assertStatus(200);
    });

});
