<?php

use App\DTO\WhatsApp\IncomingMessage;
use App\Enums\WhatsApp\ConversationState;
use App\Jobs\WhatsApp\ProcessIncomingMessageJob;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Models\WhatsApp\WhatsAppSetting;
use App\Services\Agent\TicketService;
use App\Services\WhatsApp\WhatsAppBotReleaseService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Support\Carbon;

afterEach(fn () => Mockery::close());

function incomingWa(string $from, string $body): IncomingMessage
{
    return new IncomingMessage(
        from: $from,
        body: $body,
        type: 'text',
        mediaUrl: null,
        mediaId: null,
        mimetype: null,
        timestamp: (string) time(),
        messageId: uniqid('msg_'),
    );
}

describe('Ticket Closed Bot Delay — Prevenção de reabertura por "Obrigado"', function () {

    it('ao finalizar o chamado, cliente manda Obrigado dentro do delay e o bot permanece em silêncio', function () {
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:00:00'));

        WhatsAppSetting::query()->updateOrCreate(
            ['key' => 'ticket_closed_delay_minutes'],
            ['value' => '10']
        );

        config(['whatsapp.enabled' => false]);

        $phone = '5527999991234';
        $statusClosed = Status::factory()->terminal()->create();

        $agent = \App\Models\User::factory()->agent()->create();

        $ticket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'completed_at' => null,
        ]);

        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => $phone,
            'ticket_id' => $ticket->id,
            'last_activity_at' => now(),
        ]);

        // 1. Agente finaliza o chamado
        $this->actingAs($agent, 'admin');
        $ticketService = app(TicketService::class);
        $ticketService->closeTicket($ticket, $statusClosed->id);

        $conversation->refresh();
        expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conversation->getPayloadValue('bot_release_after'))->toBe('2026-08-28T10:10:00-03:00');

        // 2. Cliente responde "Muito obrigado!" 1 minuto depois
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:01:00'));

        $job = new ProcessIncomingMessageJob(incomingWa($phone, 'Muito obrigado!'));
        $job->handle(app(WhatsAppService::class), app(\App\Services\WhatsApp\ChatBotService::class));

        // Conversas ativas no sistema: não deve ter criado nova conversa
        expect(WhatsAppConversation::where('phone', $phone)->count())->toBe(1);

        $conversation->refresh();
        expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conversation->messages()->where('body', 'Muito obrigado!')->exists())->toBeTrue();

        // 3. Cliente manda "👍" 3 minutos depois
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:03:00'));

        $job = new ProcessIncomingMessageJob(incomingWa($phone, '👍'));
        $job->handle(app(WhatsAppService::class), app(\App\Services\WhatsApp\ChatBotService::class));

        expect(WhatsAppConversation::where('phone', $phone)->count())->toBe(1);
        $conversation->refresh();
        expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING);

        // 4. Cliente manda nova mensagem APÓS os 10 minutos de delay (ex: às 10:15)
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:15:00'));

        $job = new ProcessIncomingMessageJob(incomingWa($phone, 'Olá, preciso de um novo suporte'));
        $job->handle(app(WhatsAppService::class), app(\App\Services\WhatsApp\ChatBotService::class));

        // A conversa antiga foi finalizada e uma nova foi criada no estado do bot
        $conversations = WhatsAppConversation::where('phone', $phone)->orderBy('id')->get();
        expect($conversations->count())->toBe(2);

        $oldConv = $conversations->first();
        $newConv = $conversations->last();

        expect($oldConv->state)->toBe(ConversationState::COMPLETED)
            ->and($newConv->id)->not->toBe($oldConv->id)
            ->and($newConv->state)->not->toBe(ConversationState::HUMAN_PENDING);
    });

    it('quando o delay configurado for 0, o bot é liberado imediatamente no fechamento', function () {
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:00:00'));

        WhatsAppSetting::query()->updateOrCreate(
            ['key' => 'ticket_closed_delay_minutes'],
            ['value' => '0']
        );

        config(['whatsapp.enabled' => false]);

        $phone = '5527999995555';
        $statusClosed = Status::factory()->terminal()->create();
        $agent = \App\Models\User::factory()->agent()->create();

        $ticket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'completed_at' => null,
        ]);

        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => $phone,
            'ticket_id' => $ticket->id,
            'last_activity_at' => now(),
        ]);

        $this->actingAs($agent, 'admin');
        $ticketService = app(TicketService::class);
        $ticketService->closeTicket($ticket, $statusClosed->id);

        $conversation->refresh();
        expect($conversation->state)->toBe(ConversationState::COMPLETED);
    });

    it('ao finalizar o chamado via atualização de status/observer, resposta do cliente dentro do delay não reativa o bot', function () {
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:00:00'));

        WhatsAppSetting::query()->updateOrCreate(
            ['key' => 'ticket_closed_delay_minutes'],
            ['value' => '15']
        );

        config(['whatsapp.enabled' => false]);

        $phone = '5527999997777';
        $statusClosed = Status::factory()->terminal()->create();
        $agent = \App\Models\User::factory()->agent()->create();

        $ticket = Ticket::factory()->create([
            'agent_id' => $agent->id,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'completed_at' => null,
        ]);

        $conversation = WhatsAppConversation::factory()->humanPending()->create([
            'phone' => $phone,
            'ticket_id' => $ticket->id,
            'last_activity_at' => now(),
        ]);

        $this->actingAs($agent, 'admin');

        // Simula atualização direta/via observer (ex: edição de chamado)
        $ticket->update([
            'status_id' => $statusClosed->id,
            'completed_at' => now(),
        ]);

        // Cliente responde 5 minutos depois dentro do delay de 15 min
        Carbon::setTestNow(Carbon::parse('2026-08-28 10:05:00'));

        $job = new ProcessIncomingMessageJob(incomingWa($phone, 'Valeu, muito obrigado!'));
        $job->handle(app(WhatsAppService::class), app(\App\Services\WhatsApp\ChatBotService::class));

        // Não deve criar nova conversa e o bot não deve assumir
        expect(WhatsAppConversation::where('phone', $phone)->count())->toBe(1);
        $conversation->refresh();
        expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
            ->and($conversation->messages()->where('body', 'Valeu, muito obrigado!')->exists())->toBeTrue();
    });

});
