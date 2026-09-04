<?php

use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use App\Services\WhatsApp\ChatBotService;
use App\Services\WhatsApp\WhatsAppService;

// ─────────────────────────────────────────────────────────────────────────────
// ProcessIncomingMessageJob
// ─────────────────────────────────────────────────────────────────────────────

function makeIncoming(string $messageId = 'msg-test-001'): IncomingMessage
{
    return new IncomingMessage(
        from:      '5527999990000',
        body:      'Olá',
        type:      'text',
        mediaUrl:  null,
        mediaId:   null,
        mimetype:  null,
        timestamp: (string) time(),
        messageId: $messageId,
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// handle
// ─────────────────────────────────────────────────────────────────────────────

describe('ProcessIncomingMessageJob::handle()', function () {

    it('pula processamento quando mensagem já foi processada (idempotência)', function () {
        $whatsApp = $this->mock(WhatsAppService::class);
        $chatBot  = $this->mock(ChatBotService::class);

        $msg = makeIncoming('msg-duplicado');

        // Cria registro existente
        WhatsAppMessage::factory()->create(['provider_message_id' => 'msg-duplicado']);

        $whatsApp->shouldReceive('alreadyProcessed')
            ->with('msg-duplicado')
            ->andReturn(true);

        $chatBot->shouldNotReceive('handle');

        $job = new ProcessIncomingMessageJob($msg);
        $job->handle($whatsApp, $chatBot);
    });

    it('processa nova mensagem criando conversa e delegando ao chatbot', function () {
        $whatsApp = $this->mock(WhatsAppService::class);
        $chatBot  = $this->mock(ChatBotService::class);

        $msg = makeIncoming('msg-nova-001');

        $whatsApp->shouldReceive('alreadyProcessed')->with('msg-nova-001')->andReturn(false);
        $whatsApp->shouldReceive('recordInbound')->once();
        $chatBot->shouldReceive('handle')->once();

        $job = new ProcessIncomingMessageJob($msg);
        $job->handle($whatsApp, $chatBot);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone' => '5527999990000',
        ]);
    });

    it('reutiliza conversa ativa existente', function () {
        $whatsApp = $this->mock(WhatsAppService::class);
        $chatBot  = $this->mock(ChatBotService::class);

        $existing = WhatsAppConversation::factory()->create([
            'phone'            => '5527999990000',
            'state'            => ConversationState::AWAITING_NAME,
            'last_activity_at' => now()->subMinutes(5),
        ]);

        $whatsApp->shouldReceive('alreadyProcessed')->andReturn(false);
        $whatsApp->shouldReceive('recordInbound')->once();
        $chatBot->shouldReceive('handle')->once()->with(
            Mockery::on(fn ($conv) => $conv->id === $existing->id),
            Mockery::any()
        );

        $job = new ProcessIncomingMessageJob(makeIncoming('msg-reutiliza'));
        $job->handle($whatsApp, $chatBot);

        expect(WhatsAppConversation::where('phone', '5527999990000')->count())->toBe(1);
    });

    it('cria nova conversa quando existente está em estado terminal', function () {
        $whatsApp = $this->mock(WhatsAppService::class);
        $chatBot  = $this->mock(ChatBotService::class);

        WhatsAppConversation::factory()->completed()->create([
            'phone' => '5527111110000',
        ]);

        $msg = new IncomingMessage(
            from: '5527111110000', body: 'oi', type: 'text',
            mediaUrl: null, mediaId: null, mimetype: null,
            timestamp: (string) time(), messageId: 'msg-new-session',
        );

        $whatsApp->shouldReceive('alreadyProcessed')->andReturn(false);
        $whatsApp->shouldReceive('recordInbound')->once();
        $chatBot->shouldReceive('handle')->once();

        $job = new ProcessIncomingMessageJob($msg);
        $job->handle($whatsApp, $chatBot);

        expect(WhatsAppConversation::where('phone', '5527111110000')->count())->toBe(2);
    });

    it('cria nova conversa quando existente está expirada', function () {
        $whatsApp = $this->mock(WhatsAppService::class);
        $chatBot  = $this->mock(ChatBotService::class);

        WhatsAppConversation::factory()->expired()->create([
            'phone' => '5527222220000',
        ]);

        $msg = new IncomingMessage(
            from: '5527222220000', body: 'oi', type: 'text',
            mediaUrl: null, mediaId: null, mimetype: null,
            timestamp: (string) time(), messageId: 'msg-after-expiry',
        );

        $whatsApp->shouldReceive('alreadyProcessed')->andReturn(false);
        $whatsApp->shouldReceive('recordInbound')->once();
        $chatBot->shouldReceive('handle')->once();

        $job = new ProcessIncomingMessageJob($msg);
        $job->handle($whatsApp, $chatBot);

        expect(WhatsAppConversation::where('phone', '5527222220000')->count())->toBe(2);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Configurações do job
// ─────────────────────────────────────────────────────────────────────────────

describe('ProcessIncomingMessageJob — configurações', function () {

    it('define backoff com 3 tentativas em intervalos crescentes', function () {
        $job = new ProcessIncomingMessageJob(makeIncoming());

        expect($job->backoff())->toBe([10, 30, 60]);
    });

    it('define tries como 3', function () {
        $job = new ProcessIncomingMessageJob(makeIncoming());

        expect($job->tries)->toBe(3);
    });

    it('define timeout como 60 segundos', function () {
        $job = new ProcessIncomingMessageJob(makeIncoming());

        expect($job->timeout)->toBe(60);
    });

    it('loga erro no método failed()', function () {
        $job = new ProcessIncomingMessageJob(makeIncoming('msg-failed'));

        // Não deve lançar exceção
        expect(fn () => $job->failed(new \RuntimeException('Erro simulado')))->not->toThrow(\Throwable::class);
    });

});
