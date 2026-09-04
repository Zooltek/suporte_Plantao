<?php

use App\Models\WhatsApp\WhatsAppBotMessage;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppMessage;
use Illuminate\Support\Facades\Http;

// ─────────────────────────────────────────────────────────────────────────────
// Admin WhatsApp Controller — Feature Tests
// ─────────────────────────────────────────────────────────────────────────────

// ─────────────────────────────────────────────────────────────────────────────
// Autenticação e Autorização
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /admin/whatsapp — autenticação', function () {

    it('redireciona visitante não autenticado', function () {
        $this->get('/admin/whatsapp')
            ->assertRedirect();
    });

    it('permite acesso a administrador', function () {
        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertStatus(200);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// Dados exibidos
// ─────────────────────────────────────────────────────────────────────────────

describe('GET /admin/whatsapp — conteúdo', function () {

    it('exibe a view correta', function () {
        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertStatus(200)
            ->assertViewIs('admin.whatsapp.index');
    });

    it('exibe totalizadores zerados quando não há conversas', function () {
        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total_conversations'] === 0
                    && $stats['active'] === 0
                    && $stats['completed'] === 0
                    && $stats['tickets_created'] === 0;
            });
    });

    it('exibe totalizadores corretos com conversas cadastradas', function () {
        WhatsAppConversation::factory()->completed()->create(['ticket_id' => null]);
        WhatsAppConversation::factory()->completed()->create(['ticket_id' => null]);
        WhatsAppConversation::factory()->awaitingName()->create();
        WhatsAppConversation::factory()->cancelled()->create();

        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertStatus(200)
            ->assertViewHas('stats', function ($stats) {
                return $stats['total_conversations'] === 4
                    && $stats['completed'] === 2
                    && $stats['active'] === 1; // awaitingName = ativo, cancelled = terminal
            });
    });

    it('passa configuração técnica para a view', function () {
        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertViewHas('config', function ($config) {
                return isset($config['enabled'])
                    && isset($config['provider'])
                    && isset($config['from_number'])
                    && isset($config['local_test_numbers'])
                    && isset($config['webhook_url']);
            });
    });

    it('exibe os números locais de homologação mantendo o oficial com ddd 27', function () {
        actingAsAdmin();

        config([
            'whatsapp.from_number' => '27981180125',
            'whatsapp.local_test_numbers' => ['27981180125', '45999178290'],
        ]);

        $this->get('/admin/whatsapp')
            ->assertSee('+27981180125')
            ->assertSee('+27981180125, +45999178290');
    });

    it('exibe conversas recentes na view', function () {
        WhatsAppConversation::factory()->count(3)->create();

        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertViewHas('recentConversations', function ($conversations) {
                return $conversations->count() === 3;
            });
    });

    it('exibe URL do webhook corretamente', function () {
        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertSee('api/webhook/whatsapp');
    });

    it('explica quando a instancia esta conectada mas o envio automatico esta desabilitado', function () {
        actingAsAdmin();

        config([
            'whatsapp.provider' => 'evolution',
            'whatsapp.enabled' => false,
        ]);

        $this->get('/admin/whatsapp')
            ->assertSee('WhatsApp conectado, mas integração desabilitada.')
            ->assertSee('As mensagens são registradas no sistema, porém o envio automático ao provedor está desligado.');
    });

    it('mantem a mensagem de bot ativo quando a integracao esta habilitada', function () {
        actingAsAdmin();

        config([
            'whatsapp.provider' => 'evolution',
            'whatsapp.enabled' => true,
        ]);

        $this->get('/admin/whatsapp')
            ->assertSee('WhatsApp conectado com sucesso!')
            ->assertSee('O bot está ativo e pronto para receber mensagens.');
    });

    it('limita conversas recentes a 20', function () {
        WhatsAppConversation::factory()->count(25)->create();

        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertViewHas('recentConversations', function ($conversations) {
                return $conversations->count() === 20;
            });
    });

    it('conta corretamente tickets_created', function () {
        WhatsAppConversation::factory()->create(['ticket_id' => 1]);
        WhatsAppConversation::factory()->create(['ticket_id' => 2]);
        WhatsAppConversation::factory()->create(['ticket_id' => null]);

        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertViewHas('stats', fn ($s) => $s['tickets_created'] === 2);
    });

    it('exibe a mensagem padrao do bot quando o texto salvo esta vazio', function () {
        WhatsAppBotMessage::query()->create([
            'key' => 'status_not_found',
            'step' => 'consulta_chamado',
            'text' => '',
            'is_active' => true,
        ]);

        actingAsAdmin();

        $this->get('/admin/whatsapp')
            ->assertOk()
            ->assertSee('Não encontramos chamados em seu nome');
    });

});

describe('POST /admin/whatsapp/bot-messages', function () {

    it('persiste is_active como false quando o checkbox não é enviado', function () {
        actingAsAdmin();

        $this->post(route('admin.whatsapp.bot-messages.save'), [
            'key' => 'not_found_acknowledged',
            'step' => 'cliente_nao_localizado',
            'text' => 'Show! Já anotei tudo.',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $message = WhatsAppBotMessage::query()->where('key', 'not_found_acknowledged')->first();

        expect($message)->not->toBeNull()
            ->and($message->is_active)->toBeFalse();
    });

    it('persiste is_active como true quando o checkbox é marcado', function () {
        actingAsAdmin();

        $this->post(route('admin.whatsapp.bot-messages.save'), [
            'key' => 'not_found_acknowledged',
            'step' => 'cliente_nao_localizado',
            'text' => 'Show! Já anotei tudo.',
            'is_active' => '1',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $message = WhatsAppBotMessage::query()->where('key', 'not_found_acknowledged')->first();

        expect($message)->not->toBeNull()
            ->and($message->is_active)->toBeTrue();
    });

    it('exibe mensagem como inativa após salvar sem checkbox', function () {
        actingAsAdmin();

        $this->post(route('admin.whatsapp.bot-messages.save'), [
            'key' => 'not_found_acknowledged',
            'step' => 'cliente_nao_localizado',
            'text' => 'Show! Já anotei tudo.',
        ])->assertRedirect();

        $this->get('/admin/whatsapp')
            ->assertOk()
            ->assertViewHas('botMessages', function ($messages): bool {
                $message = collect($messages)->firstWhere('key', 'not_found_acknowledged');

                return is_array($message) && $message['is_active'] === false;
            });
    });

});

describe('GET /admin/whatsapp/{conversation}/messages', function () {

    it('redireciona visitante não autenticado', function () {
        $conversation = WhatsAppConversation::factory()->create();

        $this->getJson(route('admin.whatsapp.messages', $conversation))
            ->assertUnauthorized();
    });

    it('retorna apenas mensagens novas para atualizar a tela sem F5', function () {
        $conversation = WhatsAppConversation::factory()->create();
        $otherConversation = WhatsAppConversation::factory()->create();

        $oldMessage = WhatsAppMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Mensagem já renderizada',
        ]);

        $newMessage = WhatsAppMessage::factory()->create([
            'conversation_id' => $conversation->id,
            'body' => 'Mensagem nova do cliente',
        ]);

        WhatsAppMessage::factory()->create([
            'conversation_id' => $otherConversation->id,
            'body' => 'Mensagem de outra conversa',
        ]);

        actingAsAdmin();

        $this->getJson(route('admin.whatsapp.messages', [
            'conversation' => $conversation,
            'after_id' => $oldMessage->id,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'messages')
            ->assertJsonPath('messages.0.id', $newMessage->id)
            ->assertJsonPath('messages.0.body', 'Mensagem nova do cliente')
            ->assertJsonPath('messages_count', 2);
    });

    it('renderiza a tela da conversa com a url de polling', function () {
        $conversation = WhatsAppConversation::factory()->create();

        actingAsAdmin();

        $this->get(route('admin.whatsapp.show', $conversation))
            ->assertOk()
            ->assertSee(route('admin.whatsapp.messages', $conversation), false);
    });
});

describe('GET /admin/whatsapp/qr-code — Evolution API', function () {

    beforeEach(function () {
        actingAsAdmin();

        config([
            'whatsapp.provider' => 'evolution',
            'whatsapp.api_url' => 'https://evolution.test',
            'whatsapp.evolution_instance' => 'amura-test',
            'whatsapp.evolution_api_key' => 'test-api-key-123',
            'whatsapp.webhook_url' => 'http://suporte12_app:8080/api/webhook/whatsapp',
        ]);
    });

    it('retorna status pending sem erro HTTP quando o QR ainda nao esta disponivel', function () {
        Http::fake([
            'https://evolution.test/instance/connect/amura-test' => Http::response([
                'count' => 0,
            ], 200),
        ]);

        $this->getJson('/admin/whatsapp/qr-code')
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
                'state' => 'connecting',
            ]);
    });

    it('recria a instancia automaticamente quando a Evolution retorna 404 no QR Code', function () {
        Http::fake([
            'https://evolution.test/instance/connect/amura-test' => Http::response([
                'error' => 'not found',
            ], 404),
            'https://evolution.test/instance/fetchInstances' => Http::response([], 200),
            'https://evolution.test/instance/create' => Http::response([
                'instance' => ['instanceName' => 'amura-test'],
            ], 201),
            'https://evolution.test/webhook/find/amura-test' => Http::response(null, 200),
            'https://evolution.test/webhook/set/amura-test' => Http::response([
                'enabled' => true,
            ], 201),
        ]);

        $this->getJson('/admin/whatsapp/qr-code')
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
                'state' => 'initializing',
            ]);
    });

    it('retorna pending quando a Evolution ainda esta inicializando e a conexao falha', function () {
        Http::fake([
            'https://evolution.test/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('connection refused'),
        ]);

        $this->getJson('/admin/whatsapp/qr-code')
            ->assertOk()
            ->assertJson([
                'status' => 'pending',
                'state' => 'booting',
            ]);
    });

    it('retorna o QR Code quando a Evolution responde com base64', function () {
        Http::fake([
            'https://evolution.test/instance/connect/amura-test' => Http::response([
                'base64' => 'data:image/png;base64,abc123',
                'code' => 'QR-CODE-123',
            ], 200),
        ]);

        $this->getJson('/admin/whatsapp/qr-code')
            ->assertOk()
            ->assertJson([
                'status' => 'ready',
                'base64' => 'data:image/png;base64,abc123',
                'code' => 'QR-CODE-123',
            ]);
    });
});

describe('POST /admin/whatsapp/{conversation}/release', function () {

    beforeEach(function () {
        actingAsAdmin();
    });

    it('reativa o bot retornando a conversa para GREETING', function () {
        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'last_activity_at' => now()->subMinutes(15),
        ]);

        $this->post(route('admin.whatsapp.release', $conversation))
            ->assertRedirect()
            ->assertSessionHas('success');

        $conversation->refresh();

        expect($conversation->state)->toBe(\App\Enums\WhatsApp\ConversationState::GREETING)
            ->and($conversation->last_activity_at->isAfter(now()->subMinute()))->toBeTrue();
    });

    it('mantem a conversa inalterada quando ela nao esta em atendimento humano', function () {
        $conversation = WhatsAppConversation::factory()->awaitingMenu()->create();

        $this->post(route('admin.whatsapp.release', $conversation))
            ->assertRedirect()
            ->assertSessionHas('warning');

        expect($conversation->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::AWAITING_MENU);
    });
});

describe('POST /admin/whatsapp/{conversation}/pause — pausar bot (atendimento humano)', function () {

    beforeEach(function () {
        actingAsAdmin();
    });

    it('pausa o bot mudando a conversa para HUMAN_PENDING', function () {
        $conversation = WhatsAppConversation::factory()->awaitingMenu()->create([
            'last_activity_at' => now()->subMinutes(15),
        ]);

        $this->post(route('admin.whatsapp.pause', $conversation))
            ->assertRedirect()
            ->assertSessionHas('success');

        $conversation->refresh();

        expect($conversation->state)->toBe(\App\Enums\WhatsApp\ConversationState::HUMAN_PENDING)
            ->and($conversation->last_activity_at->isAfter(now()->subMinute()))->toBeTrue();
    });

    it('retorna warning quando a conversa já está em atendimento humano', function () {
        $conversation = WhatsAppConversation::factory()->humanPending()->create();

        $this->post(route('admin.whatsapp.pause', $conversation))
            ->assertRedirect()
            ->assertSessionHas('warning');

        expect($conversation->fresh()->state)->toBe(\App\Enums\WhatsApp\ConversationState::HUMAN_PENDING);
    });
});

describe('GET /admin/whatsapp/conversations/recent — reconciliação automática', function () {

    it('exige autenticação', function () {
        $this->getJson(route('admin.whatsapp.conversations.recent'))
            ->assertStatus(401);
    });

    it('retorna lista vazia quando não há conversas', function () {
        actingAsAdmin();

        $this->getJson(route('admin.whatsapp.conversations.recent'))
            ->assertStatus(200)
            ->assertJson(['conversations' => []]);
    });

    it('retorna conversas serializadas com URLs prontas para o painel', function () {
        actingAsAdmin();

        $conversation = WhatsAppConversation::factory()->awaitingMenu()->create([
            'phone' => '5527999990000',
        ]);

        WhatsAppMessage::factory()->count(3)->create([
            'conversation_id' => $conversation->id,
        ]);

        $response = $this->getJson(route('admin.whatsapp.conversations.recent'))
            ->assertStatus(200);

        $payload = $response->json('conversations.0');

        expect($payload['id'])->toBe($conversation->id)
            ->and($payload['phone'])->toBe('5527999990000')
            ->and($payload['state'])->toBe('awaiting_menu')
            ->and($payload['state_label'])->toBe('Aguardando opção')
            ->and($payload['messages_count'])->toBe(3)
            ->and($payload['show_url'])->toBe(route('admin.whatsapp.show', $conversation->id));
    });

    it('ordena pelo created_at decrescente e respeita limite de 20', function () {
        actingAsAdmin();

        $expectedFirst = WhatsAppConversation::factory()->awaitingMenu()->create([
            'created_at' => now(),
        ]);

        WhatsAppConversation::factory()->awaitingMenu()->create([
            'created_at' => now()->subHour(),
        ]);

        $response = $this->getJson(route('admin.whatsapp.conversations.recent'))
            ->assertStatus(200);

        $first = $response->json('conversations.0');

        expect($first['id'])->toBe($expectedFirst->id)
            ->and(count($response->json('conversations')))->toBeLessThanOrEqual(20);
    });

});

// ─────────────────────────────────────────────────────────────────────────────
// POST /admin/whatsapp/settings — Configurações de Expediente e Automação
// ─────────────────────────────────────────────────────────────────────────────

describe('POST /admin/whatsapp/settings', function () {

    it('salva configurações de expediente e delay pós-encerramento', function () {
        actingAsAdmin();

        $this->post(route('admin.whatsapp.settings.update'), [
            'business_hours_start' => '08:00',
            'business_hours_end' => '18:30',
            'business_days' => '1,2,3,4,5',
            'out_of_hours_cooldown_minutes' => '60',
            'ticket_closed_delay_minutes' => '15',
        ])
            ->assertRedirect()
            ->assertSessionHas('success');

        expect(\App\Models\WhatsApp\WhatsAppSetting::where('key', 'ticket_closed_delay_minutes')->value('value'))
            ->toBe('15')
            ->and(\App\Models\WhatsApp\WhatsAppSetting::where('key', 'out_of_hours_cooldown_minutes')->value('value'))
            ->toBe('60');
    });

    it('valida delay pós-encerramento como número inteiro entre 0 e 1440', function () {
        actingAsAdmin();

        $this->post(route('admin.whatsapp.settings.update'), [
            'business_hours_start' => '08:00',
            'business_hours_end' => '18:30',
            'business_days' => '1,2,3,4,5',
            'out_of_hours_cooldown_minutes' => '60',
            'ticket_closed_delay_minutes' => '-1',
        ])
            ->assertSessionHasErrors('ticket_closed_delay_minutes');
    });

    it('exibe delay pós-encerramento carregado na view', function () {
        \App\Models\WhatsApp\WhatsAppSetting::query()->updateOrCreate(
            ['key' => 'ticket_closed_delay_minutes'],
            ['value' => '25']
        );

        actingAsAdmin();

        $this->get(route('admin.whatsapp.index'))
            ->assertOk()
            ->assertSee('value="25"', false);
    });

});
