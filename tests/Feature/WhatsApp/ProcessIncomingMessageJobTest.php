<?php

use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\ChatBotService;
use App\Services\WhatsApp\WhatsAppService;

/**
 * Testa o ProcessIncomingMessageJob isolado:
 *  - Idempotência (mensagem já processada ignorada)
 *  - Resolução de conversa (nova, ativa, terminal, expirada)
 *  - Delegação ao ChatBotService
 */

afterEach(fn () => Mockery::close());

function jobMsg(array $override = []): IncomingMessage
{
    return new IncomingMessage(
        from:      $override['from']      ?? '5527000000001',
        body:      $override['body']      ?? 'Olá',
        type:      $override['type']      ?? 'text',
        mediaUrl:  null,
        mediaId:   null,
        mimetype:  null,
        timestamp: (string) time(),
        messageId: $override['messageId'] ?? uniqid('job_'),
    );
}

function runJob(IncomingMessage $msg, WhatsAppService $wa, ChatBotService $bot): void
{
    (new ProcessIncomingMessageJob($msg))->handle($wa, $bot);
}

describe('ProcessIncomingMessageJob — idempotência', function () {

    it('mensagem já processada não invoca chatbot nem grava inbound', function () {
        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->once()->andReturn(true);
        $wa->shouldNotReceive('recordInbound');
        $bot->shouldNotReceive('handle');

        runJob(jobMsg(['messageId' => 'dup-001']), $wa, $bot);
    });

});

describe('ProcessIncomingMessageJob — resolução de conversa', function () {

    it('cria nova conversa quando não há sessão para o número', function () {
        $msg = jobMsg(['from' => '5527100000001', 'messageId' => 'new-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')->once();
        $bot->shouldReceive('handle')->once();

        runJob($msg, $wa, $bot);

        $this->assertDatabaseHas('whatsapp_conversations', [
            'phone' => '5527100000001',
            'state' => ConversationState::GREETING->value,
        ]);
    });

    it('reutiliza conversa ativa não expirada', function () {
        $existing = WhatsAppConversation::factory()->awaitingMenu()->create([
            'phone'            => '5527100000002',
            'last_activity_at' => now()->subMinutes(5),
        ]);

        $msg = jobMsg(['from' => '5527100000002', 'messageId' => 'reuse-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        expect(WhatsAppConversation::where('phone', '5527100000002')->count())->toBe(1);
    });

    it('cria nova conversa quando a existente está COMPLETED', function () {
        WhatsAppConversation::factory()->completed()->create([
            'phone' => '5527100000003',
        ]);

        $msg = jobMsg(['from' => '5527100000003', 'messageId' => 'after-done-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')->once();
        $bot->shouldReceive('handle')->once();

        runJob($msg, $wa, $bot);

        expect(WhatsAppConversation::where('phone', '5527100000003')->count())->toBe(2);
    });

    it('cria nova conversa quando a existente está CANCELLED', function () {
        WhatsAppConversation::factory()->cancelled()->create([
            'phone' => '5527100000004',
        ]);

        $msg = jobMsg(['from' => '5527100000004', 'messageId' => 'after-cancel-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')->once();
        $bot->shouldReceive('handle')->once();

        runJob($msg, $wa, $bot);

        expect(WhatsAppConversation::where('phone', '5527100000004')->count())->toBe(2);
    });

    it('cria nova conversa quando a existente está expirada', function () {
        WhatsAppConversation::factory()->expired()->create([
            'phone' => '5527100000005',
        ]);

        $msg = jobMsg(['from' => '5527100000005', 'messageId' => 'expired-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')->once();
        $bot->shouldReceive('handle')->once();

        runJob($msg, $wa, $bot);

        expect(WhatsAppConversation::where('phone', '5527100000005')->count())->toBe(2);
    });

    it('libera atendimento humano inativo por 5 minutos e cria nova conversa para o bot', function () {
        config(['whatsapp.chatbot.human_handoff_idle_minutes' => 5]);

        $existing = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => '5527100000007',
            'last_activity_at' => now()->subMinutes(6),
        ]);

        $msg = jobMsg(['from' => '5527100000007', 'messageId' => 'idle-human-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id !== $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id !== $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        $existing->refresh();

        expect($existing->state)->toBe(ConversationState::COMPLETED)
            ->and($existing->getPayloadValue('bot_auto_release_reason'))->toBe('human_handoff_idle')
            ->and(WhatsAppConversation::where('phone', '5527100000007')->count())->toBe(2);
    });

    it('mantém atendimento humano ativo antes de completar 5 minutos de inatividade', function () {
        config(['whatsapp.chatbot.human_handoff_idle_minutes' => 5]);

        $existing = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => '5527100000008',
            'last_activity_at' => now()->subMinutes(4),
        ]);

        $msg = jobMsg(['from' => '5527100000008', 'messageId' => 'active-human-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        expect($existing->fresh()->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and(WhatsAppConversation::where('phone', '5527100000008')->count())->toBe(1);
    });

    it('mantém atendimento humano ativo mesmo após 5 minutos quando vinculado a um chamado aberto', function () {
        config(['whatsapp.chatbot.human_handoff_idle_minutes' => 5]);

        $ticket = Ticket::factory()->create(['completed_at' => null, 'status_id' => Ticket::STATUS_PENDING_ID]);
        $existing = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => '5527100000009',
            'ticket_id' => $ticket->id,
            'last_activity_at' => now()->subMinutes(15),
        ]);

        $msg = jobMsg(['from' => '5527100000009', 'messageId' => 'ticket-human-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        expect($existing->fresh()->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and(WhatsAppConversation::where('phone', '5527100000009')->count())->toBe(1);
    });

    it('mantém conversa ativa em human_pending quando mensagem chega dentro do delay pós-fechamento', function () {
        $ticket = Ticket::factory()->create([
            'completed_at' => now()->subMinutes(2),
            'status_id' => \App\Models\Ticket\Status::factory()->terminal()->create()->id,
        ]);
        $existing = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => '5527100000010',
            'ticket_id' => $ticket->id,
            'payload' => [
                'ticket_closed_at' => now()->subMinutes(2)->toIso8601String(),
                'bot_release_after' => now()->addMinutes(8)->toIso8601String(),
            ],
            'last_activity_at' => now()->subMinutes(2),
        ]);

        $msg = jobMsg(['from' => '5527100000010', 'messageId' => 'ticket-delay-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id === $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        expect($existing->fresh()->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and(WhatsAppConversation::where('phone', '5527100000010')->count())->toBe(1);
    });

    it('libera conversa antiga e cria nova quando mensagem chega após expirar o delay pós-fechamento', function () {
        $ticket = Ticket::factory()->create([
            'completed_at' => now()->subMinutes(20),
            'status_id' => \App\Models\Ticket\Status::factory()->terminal()->create()->id,
        ]);
        $existing = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => '5527100000011',
            'ticket_id' => $ticket->id,
            'payload' => [
                'ticket_closed_at' => now()->subMinutes(20)->toIso8601String(),
                'bot_release_after' => now()->subMinutes(10)->toIso8601String(),
            ],
            'last_activity_at' => now()->subMinutes(10),
        ]);

        $msg = jobMsg(['from' => '5527100000011', 'messageId' => 'ticket-delay-expired-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')
            ->withArgs(fn ($conv) => $conv->id !== $existing->id)
            ->once();
        $bot->shouldReceive('handle')
            ->withArgs(fn ($conv) => $conv->id !== $existing->id)
            ->once();

        runJob($msg, $wa, $bot);

        expect($existing->fresh()->state)->toBe(ConversationState::COMPLETED)
            ->and(WhatsAppConversation::where('phone', '5527100000011')->count())->toBe(2);
    });

    it('nova conversa é criada com estado GREETING', function () {
        $msg = jobMsg(['from' => '5527100000006', 'messageId' => 'greeting-state-001']);

        $wa  = Mockery::mock(WhatsAppService::class);
        $bot = Mockery::mock(ChatBotService::class);

        $wa->shouldReceive('alreadyProcessed')->andReturn(false);
        $wa->shouldReceive('recordInbound')->once();
        $bot->shouldReceive('handle')->once();

        runJob($msg, $wa, $bot);

        $conv = WhatsAppConversation::where('phone', '5527100000006')->first();
        expect($conv->state)->toBe(ConversationState::GREETING);
    });

});
