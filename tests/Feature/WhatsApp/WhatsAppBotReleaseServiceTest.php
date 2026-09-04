<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppBotMessageService;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use App\Services\WhatsApp\WhatsAppService;

afterEach(fn () => Mockery::close());

function makeBotReleaseService(
    ?WhatsAppService $whatsApp = null,
    ?WhatsAppBotMessageService $botMessages = null,
): WhatsAppBotReleaseService {
    $whatsApp ??= Mockery::mock(WhatsAppService::class)->shouldIgnoreMissing();
    $botMessages ??= app(WhatsAppBotMessageService::class);

    return new WhatsAppBotReleaseService($whatsApp, $botMessages);
}

describe('WhatsAppBotReleaseService — releaseManually', function () {

    it('envia mensagem de finalização e reseta estado para GREETING', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $conv->id),
                Mockery::on(fn ($text) => str_contains($text, 'finalizado com sucesso'))
            );

        expect(makeBotReleaseService($whatsApp)->releaseManually($conv))->toBeTrue();

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::GREETING);
    });

    it('retorna false quando a conversa não está em HUMAN_PENDING', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseManually($conv))->toBeFalse();
    });

});

describe('WhatsAppBotReleaseService — releaseTicketConversations', function () {

    it('com delay > 0 envia mensagem de finalização e agenda bot_release_after mantendo HUMAN_PENDING', function () {
        config(['whatsapp.chatbot.ticket_closed_delay_minutes' => 10]);

        $ticket = Ticket::factory()->create();
        $conv1 = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
        ]);
        $conv2 = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->twice()
            ->with(
                Mockery::type(WhatsAppConversation::class),
                Mockery::on(fn ($text) => str_contains($text, 'finalizado com sucesso'))
            );

        $released = makeBotReleaseService($whatsApp)->releaseTicketConversations($ticket);

        expect($released)->toBe(2);

        $conv1->refresh();
        $conv2->refresh();

        expect($conv1->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conv1->getPayloadValue('bot_release_after'))->not->toBeNull()
            ->and($conv1->getPayloadValue('ticket_closed_at'))->not->toBeNull()
            ->and($conv2->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conv2->getPayloadValue('bot_release_after'))->not->toBeNull();
    });

    it('com delay = 0 envia mensagem de finalização e marca conversas imediatamente como COMPLETED', function () {
        config(['whatsapp.chatbot.ticket_closed_delay_minutes' => 0]);

        $ticket = Ticket::factory()->create();
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->with(
                Mockery::type(WhatsAppConversation::class),
                Mockery::on(fn ($text) => str_contains($text, 'finalizado com sucesso'))
            );

        $released = makeBotReleaseService($whatsApp)->releaseTicketConversations($ticket);

        expect($released)->toBe(1);
        expect($conv->fresh()->state)->toBe(ConversationState::COMPLETED);
    });

    it('não envia mensagem quando não há conversas em HUMAN_PENDING', function () {
        $ticket = Ticket::factory()->create();
        WhatsAppConversation::factory()->completed()->create(['ticket_id' => $ticket->id]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseTicketConversations($ticket))->toBe(0);
    });

});

describe('WhatsAppBotReleaseService — releaseIfHumanPendingIdle', function () {

    it('não libera conversa durante o período de delay pós-fechamento', function () {
        $statusClosed = Status::factory()->terminal()->create();
        $ticket = Ticket::factory()->create(['completed_at' => now(), 'status_id' => $statusClosed->id]);
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'payload' => [
                'ticket_closed_at' => now()->toIso8601String(),
                'bot_release_after' => now()->addMinutes(10)->toIso8601String(),
            ],
            'last_activity_at' => now()->subMinute(),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseIfHumanPendingIdle($conv))->toBeFalse();
        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('libera conversa para COMPLETED após expirar o delay pós-fechamento sem duplicar mensagem', function () {
        $statusClosed = Status::factory()->terminal()->create();
        $ticket = Ticket::factory()->create(['completed_at' => now()->subMinutes(15), 'status_id' => $statusClosed->id]);
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'payload' => [
                'ticket_closed_at' => now()->subMinutes(15)->toIso8601String(),
                'bot_release_after' => now()->subMinutes(5)->toIso8601String(),
            ],
            'last_activity_at' => now()->subMinutes(5),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        // Mensagem de finalização já foi enviada no fechamento do chamado, não deve reenviar
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseIfHumanPendingIdle($conv))->toBeTrue();
        expect($conv->fresh()->state)->toBe(ConversationState::COMPLETED);
    });

    it('envia mensagem de finalização e marca conversa idle sem chamado como COMPLETED', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now()->subHour(),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldReceive('send')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $conv->id),
                Mockery::on(fn ($text) => str_contains($text, 'finalizado com sucesso'))
            );

        expect(makeBotReleaseService($whatsApp)->releaseIfHumanPendingIdle($conv))->toBeTrue();
        expect($conv->fresh()->state)->toBe(ConversationState::COMPLETED);
    });

    it('não envia mensagem e não libera quando a conversa está vinculada a um chamado aberto', function () {
        $ticket = Ticket::factory()->create(['completed_at' => null, 'status_id' => Ticket::STATUS_PENDING_ID]);
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'last_activity_at' => now()->subHours(2),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseIfHumanPendingIdle($conv))->toBeFalse();
        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

    it('não envia mensagem quando a conversa ainda está ativa', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now(),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        expect(makeBotReleaseService($whatsApp)->releaseIfHumanPendingIdle($conv))->toBeFalse();
    });

});

describe('WhatsAppBotReleaseService — pauseManually', function () {

    it('pausa a conversa mudando seu estado para HUMAN_PENDING e limpando metadados de fechamento', function () {
        $conv = WhatsAppConversation::factory()->awaitingMenu()->create([
            'payload' => [
                'bot_release_after' => now()->addMinutes(10)->toIso8601String(),
                'ticket_closed_at' => now()->toIso8601String(),
                'custom_field' => 'kept',
            ],
            'last_activity_at' => now()->subMinutes(10),
        ]);

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        $result = makeBotReleaseService($whatsApp)->pauseManually($conv);

        expect($result)->toBeTrue();

        $conv->refresh();
        expect($conv->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conv->getPayloadValue('custom_field'))->toBe('kept')
            ->and($conv->getPayloadValue('bot_release_after'))->toBeNull()
            ->and($conv->getPayloadValue('ticket_closed_at'))->toBeNull()
            ->and($conv->last_activity_at->isAfter(now()->subMinute()))->toBeTrue();
    });

    it('retorna false quando a conversa já está em HUMAN_PENDING', function () {
        $conv = WhatsAppConversation::factory()->humanPending()->create();

        $whatsApp = Mockery::mock(WhatsAppService::class);
        $whatsApp->shouldNotReceive('send');

        $result = makeBotReleaseService($whatsApp)->pauseManually($conv);

        expect($result)->toBeFalse();
        expect($conv->fresh()->state)->toBe(ConversationState::HUMAN_PENDING);
    });

});
