<?php

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function aaTicket(array $attributes = []): Ticket
{
    return Ticket::factory()->create($attributes);
}

const AA_NOTES = 'Atendimento via API';

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('API V1 — Attendances — autenticação', function () {

    it('usuário não autenticado recebe 401 ao listar', function () {
        $ticket = aaTicket();

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertUnauthorized();
    });

    it('agente autenticado via sessão lista atendimentos (200)', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk()
            ->assertJsonStructure(['data']);
    });

    it('admin autenticado via sessão lista atendimentos (200)', function () {
        actingAsAdmin();
        $ticket = aaTicket();

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk();
    });

    it('agente autenticado via ip lista atendimentos de ticket pendente sem tecnico', function () {
        actingAsAgent();
        $ticket = aaTicket([
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => null,
        ]);

        $this->withServerVariables([
            'HTTP_HOST' => '192.168.1.62:8090',
            'REMOTE_ADDR' => '192.168.1.99',
        ])->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk()
            ->assertJsonStructure(['data']);
    });

});

describe('API V1 — Attendances — index', function () {

    it('retorna os atendimentos do ticket em ordem decrescente', function () {
        $user   = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        Attendance::factory()->create([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'notes'      => 'Primeiro atendimento',
            'created_at' => now()->subHour(),
        ]);
        Attendance::factory()->create([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'notes'      => 'Segundo atendimento',
            'created_at' => now(),
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}/attendances");

        $response->assertOk()
            ->assertJsonCount(2, 'data');

        $first = $response->json('data.0.notes');
        expect($first)->toBe('Segundo atendimento');
    });

    it('retorna lista vazia quando o ticket não tem atendimentos', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

    it('não retorna atendimentos de outro ticket', function () {
        $user   = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);
        $other  = aaTicket();

        Attendance::factory()->create(['ticket_id' => $other->id, 'user_id' => $user->id]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    });

});

describe('API V1 — Attendances — store', function () {

    it('cria atendimento com dados mínimos', function () {
        $user   = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes' => AA_NOTES,
        ])->assertCreated()
            ->assertJsonFragment(['notes' => AA_NOTES]);

        $this->assertDatabaseHas('attendances', [
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'notes'     => AA_NOTES,
        ]);
    });

    it('via de retorno sem agendamento registra retorno realizado', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes'      => 'Com retorno',
            'return_zap' => true,
        ])->assertCreated();

        $this->assertDatabaseHas('attendances', [
            'ticket_id'   => $ticket->id,
            'returned_by' => $user->id,
        ]);
    });

    it('return_user_id é opcional quando o retorno já foi realizado', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes'      => 'Retorno sem responsável',
            'return_tel' => true,
        ])->assertCreated();
    });

    it('return_at é opcional quando o retorno já foi realizado', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes'      => 'Retorno sem data',
            'return_cel' => true,
        ])->assertCreated();
    });

    it('com técnico ou data informados registra retorno agendado', function () {
        $user = actingAsAgent();
        $assignee = \App\Models\User::factory()->agent()->create();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes' => 'Agendar retorno',
            'return_zap' => true,
            'return_user_id' => $assignee->id,
        ])->assertCreated();

        $this->assertDatabaseHas('attendances', [
            'ticket_id' => $ticket->id,
            'return_assigned_to' => $assignee->id,
            'returned_by' => null,
        ]);
    });

    it('return_user_id inválido falha com 422', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes'           => 'Teste',
            'return_user_id'  => 999999,
        ])->assertUnprocessable();
    });

    it('return_at com formato inválido falha com 422', function () {
        $user = actingAsAgent();
        $ticket = aaTicket(['agent_id' => $user->id]);

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes'     => 'Teste',
            'return_at' => 'nao-e-uma-data',
        ])->assertUnprocessable();
    });

    it('ticket inexistente retorna 404', function () {
        actingAsAgent();

        $this->postJson('/api/v1/tickets/999999/attendances', [
            'notes' => 'Teste',
        ])->assertNotFound();
    });

    it('agente via ip cria atendimento em ticket pendente sem tecnico', function () {
        $user = actingAsAgent();
        $ticket = aaTicket([
            'status_id' => Ticket::STATUS_PENDING_ID,
            'agent_id' => null,
        ]);

        $this->withServerVariables([
            'HTTP_HOST' => '192.168.1.62:8090',
            'REMOTE_ADDR' => '192.168.1.99',
        ])->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes' => 'Retorno registrado pela fila pendente',
            'return_zap' => true,
        ])->assertCreated()
            ->assertJsonFragment(['notes' => 'Retorno registrado pela fila pendente']);

        $this->assertDatabaseHas('attendances', [
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'notes' => 'Retorno registrado pela fila pendente',
            'returned_by' => $user->id,
        ]);
    });

});
