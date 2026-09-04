<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Company;
use App\Models\Notification;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

beforeEach(function () {
    config([
        'whatsapp.enabled' => false,
        'whatsapp.provider' => 'evolution',
        'whatsapp.from_number' => '27981180125',
        'whatsapp.local_test_numbers' => ['27981180125', '45999178290'],
        'whatsapp.api_url' => 'http://suporte12_evolution:8080',
        'whatsapp.evolution_instance' => 'amura-local',
        'whatsapp.evolution_api_key' => 'amura-local-dusk-key',
    ]);
});

test('abre chamado completo pelo chatbot e exibe os números locais no painel', function () {
    ['admin' => $admin, 'company' => $company] = prepareWhatsAppDuskScenario();

    sendWebhookMessages($this, '45999178290', [
        'oi',
        '1',
        'Teste Local',
        $company->name,
        '1',
        'Erro no modulo de caixa',
        'confirmar',
        'sim',
    ]);

    $conversation = WhatsAppConversation::query()
        ->where('phone', '45999178290')
        ->latest()
        ->firstOrFail();

    expect($conversation->state)->toBe(ConversationState::COMPLETED)
        ->and($conversation->ticket_id)->not->toBeNull();

    $this->browse(function (Browser $browser) use ($admin, $company, $conversation) {
        loginIntoWhatsAppPanel($browser, $admin)
            ->assertSeeIn('@whatsapp-official-number', '+27981180125')
            ->assertSeeIn('@whatsapp-local-test-numbers', '+27981180125, +45999178290')
            ->assertSee('+45999178290')
            ->click('@conversation-link-45999178290')
            ->waitForText('Conversa #' . $conversation->id)
            ->assertSeeIn('@conversation-phone', '+45999178290')
            ->assertSeeIn('@conversation-state', 'Concluído')
            ->assertSeeIn('@conversation-company', $company->name)
            ->assertSeeIn('@conversation-area', 'Suporte Técnico')
            ->assertSeeIn('@conversation-problem', 'Erro no modulo de caixa')
            ->assertSeeIn('@conversation-ticket-badge', 'Ticket #' . $conversation->ticket_id);
    });
});

test('consulta o status do último chamado e volta ao menu principal', function () {
    ['admin' => $admin, 'company' => $company] = prepareWhatsAppDuskScenario();

    $ticket = Ticket::query()->create([
        'origin_id' => 5,
        'status_id' => 1,
        'priority_id' => 1,
        'agent_id' => $admin->id,
        'author_id' => $admin->id,
        'company_id' => $company->id,
        'contact' => 'CLIENTE LOCAL',
        'trouble' => 'Falha na impressora',
        'solution' => null,
        'obs' => 'Chamado aberto via WhatsApp. Número: 27981180125',
        'category_id' => 1,
        'sub_category_id' => 1,
        'visible' => 0,
        'subject' => 'Falha na impressora',
        'content' => 'Falha na impressora',
        'user_id' => $admin->id,
    ]);

    sendWebhookMessages($this, '27981180125', [
        'oi',
        '2',
    ]);

    $conversation = WhatsAppConversation::query()
        ->where('phone', '27981180125')
        ->latest()
        ->firstOrFail();

    expect($conversation->state)->toBe(ConversationState::AWAITING_MENU);

    $this->browse(function (Browser $browser) use ($admin, $ticket, $conversation) {
        loginIntoWhatsAppPanel($browser, $admin)
            ->click('@conversation-link-27981180125')
            ->waitForText('Conversa #' . $conversation->id)
            ->assertSeeIn('@conversation-state', 'Aguardando opção')
            ->assertSee('Seu chamado mais recente')
            ->assertSee('#' . $ticket->id)
            ->assertSee('Falha na impressora')
            ->assertSee('Aberto');
    });
});

test('transfere para atendente humano, libera o bot e reativa o menu', function () {
    ['admin' => $admin] = prepareWhatsAppDuskScenario();

    sendWebhookMessages($this, '27981180125', [
        'oi',
        '3',
    ]);

    $conversation = WhatsAppConversation::query()
        ->where('phone', '27981180125')
        ->latest()
        ->firstOrFail();

    expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
        ->and(Notification::query()->count())->toBeGreaterThan(0);

    $this->browse(function (Browser $browser) use ($admin, $conversation) {
        loginIntoWhatsAppPanel($browser, $admin)
            ->click('@conversation-link-27981180125')
            ->waitForText('Conversa #' . $conversation->id)
            ->assertSeeIn('@conversation-state', 'Aguardando atendente')
            ->click('@release-bot-button')
            ->waitForText('Saudação')
            ->assertSeeIn('@conversation-state', 'Saudação');
    });

    sendWebhookMessages($this, '27981180125', [
        'oi',
    ], startAt: 3);

    $this->browse(function (Browser $browser) use ($admin, $conversation) {
        $browser->loginAs($admin, 'admin')
            ->visit(route('admin.whatsapp.show', $conversation))
            ->waitForText('Aguardando opção')
            ->assertSeeIn('@conversation-state', 'Aguardando opção')
            ->assertSee('Como posso te ajudar?');
    });
});

function prepareWhatsAppDuskScenario(): array
{
    seedWhatsAppCatalogs();

    $company = Company::factory()->create([
        'name' => 'Empresa Local Chatbot',
        'trade_name' => 'Empresa Local Chatbot',
    ]);

    $admin = User::factory()->create([
        'name' => 'Admin Local',
        'email' => 'admin.whatsapp@example.com',
        'password' => 'password',
        'department_id' => null,
        'company_id' => null,
        'ticketit_admin' => true,
        'ticketit_agent' => true,
        'must_change_password' => false,
        'active' => true,
    ]);

    return compact('admin', 'company');
}

function seedWhatsAppCatalogs(): void
{
    DB::table('ticketit_origin')->updateOrInsert(
        ['id' => 5],
        ['name' => 'WhatsApp', 'description' => 'WhatsApp', 'status' => 1]
    );

    DB::table('ticketit_statuses')->updateOrInsert(
        ['id' => 1],
        [
            'name' => 'Aberto',
            'color' => '#2563eb',
            'is_terminal' => 0,
            'requires_schedule' => 0,
            'requires_solution' => 0,
            'requires_agent' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    DB::table('ticketit_priorities')->updateOrInsert(
        ['id' => 1],
        ['name' => 'Alta', 'color' => '#ef4444', 'created_at' => now(), 'updated_at' => now()]
    );

    DB::table('ticketit_categories')->updateOrInsert(
        ['id' => 1],
        [
            'name' => 'Suporte Técnico',
            'color' => '#2563eb',
            'avatar' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    DB::table('solutions_category')->updateOrInsert(
        ['category_id' => 1],
        [
            'parent_id' => 0,
            'sort_order' => 0,
            'status' => 1,
            'visible' => 1,
            'ticket_category_id' => 1,
            'profile' => 0,
            'header' => 1,
            'priority' => 'high',
            'created_at' => now(),
            'updated_at' => now(),
        ]
    );

    DB::table('solutions_category_description')->updateOrInsert(
        ['category_id' => 1],
        [
            'name' => 'Suporte Técnico',
            'description' => 'Suporte Técnico',
            'permalink' => 'suporte-tecnico',
        ]
    );
}

function sendWebhookMessages($testCase, string $phone, array $bodies, int $startAt = 1): void
{
    foreach (array_values($bodies) as $index => $body) {
        $sequence = $startAt + $index;

        $testCase->postJson(
            '/api/webhook/whatsapp',
            evolutionWebhookPayload($phone, (string) $body, $sequence),
            ['apikey' => config('whatsapp.evolution_api_key')]
        )
            ->assertOk()
            ->assertJson(['status' => 'queued']);
    }
}

function evolutionWebhookPayload(string $phone, string $body, int $sequence): array
{
    return [
        'event' => 'messages.upsert',
        'instance' => 'amura-local',
        'data' => [
            'key' => [
                'remoteJid' => $phone . '@s.whatsapp.net',
                'fromMe' => false,
                'id' => sprintf('dusk-%s-%02d', $phone, $sequence),
            ],
            'pushName' => 'Contato Dusk',
            'message' => [
                'conversation' => $body,
            ],
            'messageType' => 'conversation',
            'messageTimestamp' => now()->timestamp + $sequence,
        ],
    ];
}

function loginIntoWhatsAppPanel(Browser $browser, User $admin): Browser
{
    return $browser->loginAs($admin, 'admin')
        ->visit(route('admin.whatsapp.index'))
        ->waitForText('WhatsApp Business');
}
