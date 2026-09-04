<?php

use App\Enums\WhatsApp\ConversationState;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\WhatsApp\WhatsAppConversation;
use App\Services\WhatsApp\WhatsAppService;

function startable_ticket(User $agent, array $attributes = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'author_id' => $agent->id,
        'user_id' => $agent->id,
        'agent_id' => $agent->id,
        'department_id' => $agent->department_id,
    ], $attributes));
}

beforeEach(function () {
    // Desliga o envio real ao provedor: o fluxo de domínio é exercido sem rede.
    config(['whatsapp.enabled' => false]);
});

it('inicia conversa WhatsApp a partir do chamado e envia a primeira mensagem', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '5527999990000',
        'message' => 'Olá! Aqui é o suporte Amura.',
    ])
        ->assertRedirectToRoute('agent.ticket.show', ['ticket' => $ticket->id, 'tab' => 'whatsapp'])
        ->assertSessionHas('status');

    $conversation = WhatsAppConversation::where('ticket_id', $ticket->id)->firstOrFail();

    expect($conversation->state)->toBe(ConversationState::HUMAN_PENDING)
        ->and($conversation->phone)->toBe('5527999990000')
        ->and((int) $conversation->company_id)->toBe((int) $ticket->company_id);

    $this->assertDatabaseHas('whatsapp_messages', [
        'conversation_id' => $conversation->id,
        'direction' => 'outbound',
        'body' => 'Olá! Aqui é o suporte Amura.',
        'user_id' => $agent->id,
    ]);
});

it('normaliza o telefone removendo a máscara antes de salvar', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '+55 (27) 99999-0000',
        'message' => 'Mensagem inicial.',
    ])->assertRedirectToRoute('agent.ticket.show', ['ticket' => $ticket->id, 'tab' => 'whatsapp']);

    $this->assertDatabaseHas('whatsapp_conversations', [
        'ticket_id' => $ticket->id,
        'phone' => '5527999990000',
    ]);
});

it('exige telefone e mensagem', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [])
        ->assertSessionHasErrors(['phone', 'message']);

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('recusa telefone em formato inválido', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '123',
        'message' => 'Mensagem inicial.',
    ])->assertSessionHasErrors('phone');

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('bloqueia início quando o chamado já possui conversa vinculada', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);
    WhatsAppConversation::factory()->humanPending()->create(['ticket_id' => $ticket->id]);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '5527999990000',
        'message' => 'Mensagem inicial.',
    ])->assertSessionHasErrors('phone');

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->count())->toBe(1);
});

it('bloqueia início quando já existe conversa ativa para o mesmo número', function () {
    $agent = actingAsAgent();
    $otherTicket = startable_ticket($agent);
    WhatsAppConversation::factory()->humanPending()->create([
        'ticket_id' => $otherTicket->id,
        'phone' => '5527999990000',
        'last_activity_at' => now(),
    ]);

    $ticket = startable_ticket($agent);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '5527999990000',
        'message' => 'Mensagem inicial.',
    ])->assertSessionHasErrors('phone');

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('recusa início quando o número não existe no WhatsApp', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $whatsApp = Mockery::mock(WhatsAppService::class);
    $whatsApp->shouldReceive('numberExists')->once()->with('5527999990000')->andReturnFalse();
    $whatsApp->shouldReceive('send')->never();
    app()->instance(WhatsAppService::class, $whatsApp);

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '5527999990000',
        'message' => 'Mensagem inicial.',
    ])->assertSessionHasErrors('phone');

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('impede agente sem permissão no chamado de iniciar conversa', function () {
    $owner = User::factory()->agent()->create();
    $ticket = startable_ticket($owner);
    actingAsAgent();

    $this->post(route('agent.ticket.whatsapp.start', $ticket), [
        'phone' => '5527999990000',
        'message' => 'Mensagem inicial.',
    ])->assertForbidden();

    expect(WhatsAppConversation::where('ticket_id', $ticket->id)->exists())->toBeFalse();
});

it('exibe o formulário de iniciar conversa quando o chamado não tem conversa WhatsApp', function () {
    $agent = actingAsAgent();
    $ticket = startable_ticket($agent);

    $this->get(route('agent.ticket.show', $ticket))
        ->assertOk()
        ->assertSee('Iniciar conversa no WhatsApp')
        ->assertSee(route('agent.ticket.whatsapp.start', $ticket), false);
});
