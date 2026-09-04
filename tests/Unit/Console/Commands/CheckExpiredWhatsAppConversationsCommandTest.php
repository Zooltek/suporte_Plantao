<?php

use App\Console\Commands\CheckExpiredWhatsAppConversationsCommand;
use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-05-05 09:00:00'));
    config(['whatsapp.chatbot.session_ttl_minutes' => 5]);
});

afterEach(function () {
    Carbon::setTestNow();
});

describe('CheckExpiredWhatsAppConversationsCommand', function () {

    it('reseta conversas expiradas para completed', function () {
        $expired = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_PROBLEM,
            'last_activity_at' => now()->subMinutes(10),
            'payload' => ['name' => 'João', 'problem' => 'teste'],
            'company_id' => 1,
        ]);

        $active = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_PROBLEM,
            'last_activity_at' => now()->subMinutes(2),
            'payload' => ['name' => 'Maria'],
        ]);

        $this->artisan(CheckExpiredWhatsAppConversationsCommand::class)
            ->assertSuccessful()
            ->expectsOutput('1 conversas expiradas foram resetadas.');

        $expired->refresh();
        expect($expired->state)->toBe(ConversationState::COMPLETED)
            ->and($expired->payload)->toBeEmpty()
            ->and($expired->company_id)->toBeNull()
            ->and($expired->completed_at)->not->toBeNull();

        $active->refresh();
        expect($active->state)->toBe(ConversationState::AWAITING_PROBLEM);
    });

    it('ignora conversas já terminadas ou human_pending ativas', function () {
        config(['whatsapp.chatbot.human_handoff_idle_minutes' => 60]);

        WhatsAppConversation::factory()->completed()->create([
            'last_activity_at' => now()->subMinutes(30),
        ]);

        WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now()->subMinutes(2),
        ]);

        $this->artisan(CheckExpiredWhatsAppConversationsCommand::class)
            ->assertSuccessful()
            ->expectsOutput('0 conversas expiradas foram resetadas.');
    });

    it('reseta conversas human_pending que expiraram delay pós-encerramento', function () {
        $statusClosed = \App\Models\Ticket\Status::factory()->terminal()->create();
        $ticket = \App\Models\Ticket\Ticket::factory()->create([
            'completed_at' => now()->subMinutes(20),
            'status_id' => $statusClosed->id,
        ]);

        $expiredHandoff = WhatsAppConversation::factory()->humanPending()->create([
            'ticket_id' => $ticket->id,
            'payload' => [
                'ticket_closed_at' => now()->subMinutes(20)->toIso8601String(),
                'bot_release_after' => now()->subMinutes(10)->toIso8601String(),
            ],
            'last_activity_at' => now()->subMinutes(10),
        ]);

        $this->artisan(CheckExpiredWhatsAppConversationsCommand::class)
            ->assertSuccessful()
            ->expectsOutput('1 conversas expiradas foram resetadas.');

        expect($expiredHandoff->fresh()->state)->toBe(ConversationState::COMPLETED);
    });

    it('usa o TTL configurado em minutos', function () {
        config(['whatsapp.chatbot.session_ttl_minutes' => 3]);

        $almostExpired = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_AREA,
            'last_activity_at' => now()->subMinutes(4),
        ]);

        $stillActive = WhatsAppConversation::factory()->create([
            'state' => ConversationState::AWAITING_AREA,
            'last_activity_at' => now()->subMinutes(2),
        ]);

        $this->artisan(CheckExpiredWhatsAppConversationsCommand::class)
            ->assertSuccessful()
            ->expectsOutput('1 conversas expiradas foram resetadas.');

        $almostExpired->refresh();
        expect($almostExpired->state)->toBe(ConversationState::COMPLETED);

        $stillActive->refresh();
        expect($stillActive->state)->toBe(ConversationState::AWAITING_AREA);
    });
});
