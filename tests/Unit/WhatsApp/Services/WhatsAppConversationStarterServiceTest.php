<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\AgentMessageSignature;
use App\Services\WhatsApp\WhatsAppConversationStarterService;
use App\Services\WhatsApp\WhatsAppService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->whatsApp = Mockery::mock(WhatsAppService::class);
    $this->service = new WhatsAppConversationStarterService($this->whatsApp, new AgentMessageSignature);
});

it('cria a conversa em HUMAN_PENDING vinculada ao chamado e envia a primeira mensagem', function () {
    $user = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $this->whatsApp->shouldReceive('numberExists')->once()->andReturnTrue();
    $this->whatsApp->shouldReceive('send')->once()
        ->with(
            Mockery::type(WhatsAppConversation::class),
            'Olá cliente',
            $user->id,
            false,
            "*{$user->name}*\nOlá cliente",
        );

    $conversation = $this->service->startForTicket($ticket, $user, '55 (27) 99999-0000', 'Olá cliente');

    expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
        ->and($conversation->phone)->toBe('5527999990000')
        ->and((int) $conversation->ticket_id)->toBe((int) $ticket->id)
        ->and((int) $conversation->company_id)->toBe((int) $ticket->company_id);
});

it('rejeita quando o chamado já possui conversa vinculada', function () {
    $user = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();
    WhatsAppConversation::factory()->create(['ticket_id' => $ticket->id]);

    $this->whatsApp->shouldReceive('send')->never();

    expect(fn () => $this->service->startForTicket($ticket, $user, '5527999990000', 'Oi'))
        ->toThrow(ValidationException::class);
});

it('rejeita telefone vazio após a normalização', function () {
    $user = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $this->whatsApp->shouldReceive('send')->never();

    expect(fn () => $this->service->startForTicket($ticket, $user, 'abc', 'Oi'))
        ->toThrow(ValidationException::class);
});

it('rejeita quando o número não possui conta no WhatsApp e não cria a conversa', function () {
    $user = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();

    $this->whatsApp->shouldReceive('numberExists')->once()->andReturnFalse();
    $this->whatsApp->shouldReceive('send')->never();

    expect(fn () => $this->service->startForTicket($ticket, $user, '5527999990000', 'Oi'))
        ->toThrow(ValidationException::class);

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('rejeita quando já existe conversa ativa para o mesmo número', function () {
    $user = User::factory()->agent()->create();
    $ticket = Ticket::factory()->create();
    WhatsAppConversation::factory()->humanPending()->create([
        'phone' => '5527999990000',
        'last_activity_at' => now(),
    ]);

    $this->whatsApp->shouldReceive('send')->never();

    expect(fn () => $this->service->startForTicket($ticket, $user, '5527999990000', 'Oi'))
        ->toThrow(ValidationException::class);
});
