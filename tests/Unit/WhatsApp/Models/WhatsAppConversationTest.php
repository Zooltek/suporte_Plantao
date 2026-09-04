<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use Database\Factories\WhatsApp\WhatsAppConversationFactory;

// ─────────────────────────────────────────────────────────────────────────────
// WhatsAppConversation Model
// ─────────────────────────────────────────────────────────────────────────────

describe('WhatsAppConversation::isActive()', function () {

    it('retorna true quando estado não é terminal', function (ConversationState $state) {
        $conv = WhatsAppConversation::factory()->create(['state' => $state]);
        expect($conv->isActive())->toBeTrue();
    })->with([
        ConversationState::GREETING,
        ConversationState::AWAITING_NAME,
        ConversationState::AWAITING_COMPANY,
        ConversationState::AWAITING_AREA,
        ConversationState::AWAITING_PROBLEM,
        ConversationState::AWAITING_ATTACHMENTS,
        ConversationState::CONFIRMING,
    ]);

    it('retorna false para COMPLETED', function () {
        $conv = WhatsAppConversation::factory()->completed()->create();
        expect($conv->isActive())->toBeFalse();
    });

    it('retorna false para CANCELLED', function () {
        $conv = WhatsAppConversation::factory()->cancelled()->create();
        expect($conv->isActive())->toBeFalse();
    });

});

describe('WhatsAppConversation::isExpired()', function () {

    it('retorna false quando dentro do TTL', function () {
        $conv = WhatsAppConversation::factory()->create([
            'last_activity_at' => now()->subMinutes(30),
        ]);
        expect($conv->isExpired())->toBeFalse();
    });

    it('retorna true quando além do TTL', function () {
        $conv = WhatsAppConversation::factory()->expired()->create();
        expect($conv->isExpired())->toBeTrue();
    });

    it('retorna true quando last_activity_at é null', function () {
        $conv = WhatsAppConversation::factory()->create([
            'last_activity_at' => null,
        ]);
        expect($conv->isExpired())->toBeTrue();
    });

    it('respeita TTL configurado via config', function () {
        config(['whatsapp.chatbot.session_ttl_minutes' => 10]);

        $conv = WhatsAppConversation::factory()->create([
            'last_activity_at' => now()->subMinutes(11),
        ]);

        expect($conv->isExpired())->toBeTrue();
    });

    it('não altera last_activity_at ao verificar expiração', function () {
        config(['whatsapp.chatbot.session_ttl_minutes' => 60]);

        $conv = WhatsAppConversation::factory()->create([
            'last_activity_at' => now()->subMinutes(30),
        ]);

        $original = $conv->last_activity_at->toDateTimeString();

        expect($conv->isExpired())->toBeFalse()
            ->and($conv->last_activity_at->toDateTimeString())->toBe($original);
    });

});

describe('WhatsAppConversation::getPayloadValue()', function () {

    it('retorna o valor pelo caminho dot-notation', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['name' => 'João', 'area_key' => '1'],
        ]);

        expect($conv->getPayloadValue('name'))->toBe('João')
            ->and($conv->getPayloadValue('area_key'))->toBe('1');
    });

    it('retorna default quando chave não existe', function () {
        $conv = WhatsAppConversation::factory()->create(['payload' => []]);

        expect($conv->getPayloadValue('inexistente', 'padrão'))->toBe('padrão');
    });

    it('retorna null como default quando não especificado', function () {
        $conv = WhatsAppConversation::factory()->create(['payload' => []]);

        expect($conv->getPayloadValue('chave'))->toBeNull();
    });

    it('suporta payload nulo', function () {
        $conv = WhatsAppConversation::factory()->create(['payload' => null]);

        expect($conv->getPayloadValue('key', 'def'))->toBe('def');
    });

});

describe('WhatsAppConversation::mergePayload()', function () {

    it('mescla dados no payload existente', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['name' => 'João'],
        ]);

        $conv->mergePayload(['company_name' => 'Amura']);

        expect($conv->payload)->toBe(['name' => 'João', 'company_name' => 'Amura']);
    });

    it('sobrescreve chave existente', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['name' => 'Antigo'],
        ]);

        $conv->mergePayload(['name' => 'Novo']);

        expect($conv->payload['name'])->toBe('Novo');
    });

    it('funciona com payload null', function () {
        $conv = WhatsAppConversation::factory()->create(['payload' => null]);

        $conv->mergePayload(['key' => 'value']);

        expect($conv->payload)->toBe(['key' => 'value']);
    });

});

describe('WhatsAppConversation::touch()', function () {

    it('atualiza last_activity_at ao chamar touch', function () {
        $past = now()->subHours(2);

        $conv = WhatsAppConversation::factory()->create([
            'last_activity_at' => $past,
        ]);

        $conv->touch();
        $conv->refresh();

        expect($conv->last_activity_at->isAfter($past))->toBeTrue();
    });

});

describe('WhatsAppConversation casts', function () {

    it('casteia state para o enum correto', function () {
        $conv = WhatsAppConversation::factory()->create([
            'state' => 'awaiting_name',
        ]);

        expect($conv->state)->toBe(ConversationState::AWAITING_NAME);
    });

    it('casteia payload para array', function () {
        $conv = WhatsAppConversation::factory()->create([
            'payload' => ['name' => 'Teste'],
        ]);

        expect($conv->payload)->toBeArray()
            ->and($conv->payload['name'])->toBe('Teste');
    });

    it('casteia last_activity_at para Carbon', function () {
        $conv = WhatsAppConversation::factory()->create();

        expect($conv->last_activity_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
    });

});
