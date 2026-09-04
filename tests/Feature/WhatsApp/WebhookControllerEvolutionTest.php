<?php

use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    Bus::fake();
    Carbon::setTestNow(Carbon::createFromTimestamp(1710000300));

    config([
        'whatsapp.provider' => 'evolution',
        'whatsapp.evolution_api_key' => 'evolution-secret',
    ]);

    app()->instance(\App\Contracts\WhatsApp\WhatsAppProviderContract::class, new \App\Services\WhatsApp\Providers\EvolutionApiProvider);
});

afterEach(function () {
    Carbon::setTestNow();
});

it('aceita webhook Evolution autenticado por header apikey', function () {
    $payload = [
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '5527999990000@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG-EVO-001',
            ],
            'message' => [
                'conversation' => 'Preciso de suporte',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000000,
        ],
    ];

    $this->postJson('/api/webhook/whatsapp', $payload, [
        'apikey' => 'evolution-secret',
    ])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessIncomingMessageJob::class);
});

it('despacha webhook Evolution @lid usando o telefone alternativo roteável', function () {
    $payload = [
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '124575610368142@lid',
                'remoteJidAlt' => '554591540313@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG-EVO-LID-001',
            ],
            'message' => [
                'conversation' => 'Boa noite',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000002,
        ],
    ];

    $this->postJson('/api/webhook/whatsapp', $payload, [
        'apikey' => 'evolution-secret',
    ])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessIncomingMessageJob::class, function (ProcessIncomingMessageJob $job): bool {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('message');
        $property->setAccessible(true);

        return $property->getValue($job)->from === '554591540313';
    });
});

it('aceita webhook global local da Evolution com apikey na query', function () {
    $payload = [
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '124575610368142@lid',
                'remoteJidAlt' => '554591540313@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG-EVO-GLOBAL-LID-001',
            ],
            'message' => [
                'conversation' => 'Boa noite',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000002,
        ],
    ];

    $this->postJson('/api/webhook/whatsapp?apikey=evolution-secret', $payload)
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessIncomingMessageJob::class);
});

it('despacha messages.set recente do sync pós-QR para o telefone roteável', function () {
    $payload = [
        'event' => 'messages.set',
        'instance' => 'amura-test',
        'isLatest' => true,
        'data' => [
            [
                'key' => [
                    'remoteJid' => '124575610368142@lid',
                    'remoteJidAlt' => '554591540313@s.whatsapp.net',
                    'fromMe' => false,
                    'id' => 'MSG-EVO-SET-LID-001',
                ],
                'message' => [
                    'conversation' => 'Boa noite',
                ],
                'messageType' => 'conversation',
                'messageTimestamp' => 1710000290,
            ],
        ],
    ];

    $this->postJson('/api/webhook/whatsapp', $payload, [
        'apikey' => 'evolution-secret',
    ])
        ->assertOk()
        ->assertJson(['status' => 'queued']);

    Bus::assertDispatched(ProcessIncomingMessageJob::class, function (ProcessIncomingMessageJob $job): bool {
        $reflection = new ReflectionClass($job);
        $property = $reflection->getProperty('message');
        $property->setAccessible(true);

        return $property->getValue($job)->from === '554591540313';
    });
});

it('loga metadados seguros quando payload Evolution é ignorado', function () {
    Log::shouldReceive('error')->zeroOrMoreTimes();
    Log::shouldReceive('warning')->zeroOrMoreTimes();
    Log::shouldReceive('debug')->zeroOrMoreTimes();
    Log::shouldReceive('info')
        ->with('[WhatsApp Webhook] Payload ignorado.', Mockery::on(function (array $context): bool {
            return ($context['event'] ?? null) === 'messages.upsert'
                && ($context['messageType'] ?? null) === 'conversation'
                && ($context['jid_type'] ?? null) === 'group'
                && ($context['fromMe'] ?? null) === false
                && ($context['is_group'] ?? null) === true
                && ! array_key_exists('conversation', $context)
                && ! array_key_exists('apikey', $context);
        }))
        ->once();

    $payload = [
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '120363001234567890@g.us',
                'fromMe' => false,
                'id' => 'MSG-GRUPO-IGNORADO',
            ],
            'message' => [
                'conversation' => 'texto sensível não deve ir para o log',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000290,
        ],
    ];

    $this->postJson('/api/webhook/whatsapp', $payload, [
        'apikey' => 'evolution-secret',
    ])
        ->assertOk()
        ->assertJson(['status' => 'ignored']);

    Bus::assertNothingDispatched();
});

it('rejeita webhook Evolution sem header apikey válido', function () {
    $payload = [
        'event' => 'messages.upsert',
        'data' => [
            'key' => [
                'remoteJid' => '5527999990000@s.whatsapp.net',
                'fromMe' => false,
                'id' => 'MSG-EVO-002',
            ],
            'message' => [
                'conversation' => 'Preciso de suporte',
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => 1710000001,
        ],
    ];

    $this->postJson('/api/webhook/whatsapp', $payload)
        ->assertStatus(401);

    Bus::assertNothingDispatched();
});
