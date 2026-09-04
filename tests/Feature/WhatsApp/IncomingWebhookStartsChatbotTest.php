<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;

it('inicia o chatbot ao receber mensagem inbound da Evolution', function () {
    config([
        'queue.default' => 'sync',
        'whatsapp.enabled' => false,
        'whatsapp.provider' => 'evolution',
        'whatsapp.evolution_api_key' => 'evolution-secret',
    ]);

    $this->postJson('/api/webhook/whatsapp', [
        'event' => 'messages.upsert',
        'instance' => 'amura-local',
        'data' => [
            'key' => [
                'remoteJid' => '5527999991234@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG-START-CHATBOT-001',
            ],
            'message' => [
                'conversation' => 'oi',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => now()->timestamp,
        ],
    ], [
        'apikey' => 'evolution-secret',
    ])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    $conversation = WhatsAppConversation::query()
        ->where('phone', '5527999991234')
        ->firstOrFail();

    expect($conversation->state)->toBe(ConversationState::AWAITING_COMPANY_CNPJ);

    expect(WhatsAppMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('direction', 'inbound')
        ->where('provider_message_id', 'MSG-START-CHATBOT-001')
        ->where('body', 'oi')
        ->exists())->toBeTrue();

    expect(WhatsAppMessage::query()
        ->where('conversation_id', $conversation->id)
        ->where('direction', 'outbound')
        ->where('body', config('whatsapp.messages.greeting_identified'))
        ->exists())->toBeTrue();
});
