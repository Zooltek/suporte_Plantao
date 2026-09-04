<?php

use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketIssue;
use App\Models\User;

function tiaTicket(User $owner, array $attributes = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'agent_id' => $owner->id,
        'author_id' => $owner->id,
        'user_id' => $owner->id,
    ], $attributes));
}

function tiaIssue(Ticket $ticket, User $creator, array $attributes = []): TicketIssue
{
    return TicketIssue::factory()->create(array_merge([
        'ticket_id' => $ticket->id,
        'created_by' => $creator->id,
    ], $attributes));
}

describe('API V1 — Ticket issues', function () {
    it('agente responsável lista apenas os problemas do próprio ticket', function () {
        $agent = actingAsAgent();
        $ticket = tiaTicket($agent);
        tiaIssue($ticket, $agent, ['title' => 'Primeiro problema']);
        tiaIssue($ticket, $agent, ['title' => 'Segundo problema']);

        $this->getJson("/api/v1/tickets/{$ticket->id}/issues")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['title' => 'Primeiro problema'])
            ->assertJsonFragment(['title' => 'Segundo problema']);
    });

    it('agente responsável cria problema com status aberto e autor autenticado', function () {
        $agent = actingAsAgent();
        $ticket = tiaTicket($agent);

        $this->postJson("/api/v1/tickets/{$ticket->id}/issues", [
            'title' => 'Token vencido',
            'description' => 'API retornando 401.',
        ])->assertCreated()
            ->assertJsonFragment([
                'title' => 'Token vencido',
                'description' => 'API retornando 401.',
                'status' => 'open',
            ]);

        $this->assertDatabaseHas('ticket_issues', [
            'ticket_id' => $ticket->id,
            'created_by' => $agent->id,
            'title' => 'Token vencido',
            'status' => 'open',
        ]);
    });

    it('agente responsável resolve problema e grava solucionador e data', function () {
        $agent = actingAsAgent();
        $ticket = tiaTicket($agent);
        $issue = tiaIssue($ticket, $agent, ['status' => 'open']);

        $this->patchJson("/api/v1/tickets/{$ticket->id}/issues/{$issue->id}/resolve", [
            'solution' => 'Token regenerado e salvo no ambiente.',
        ])->assertOk()
            ->assertJsonFragment([
                'id' => $issue->id,
                'status' => 'resolved',
                'solution' => 'Token regenerado e salvo no ambiente.',
            ]);

        $issue->refresh();

        expect($issue->status)->toBe('resolved')
            ->and($issue->solution)->toBe('Token regenerado e salvo no ambiente.')
            ->and((int) $issue->resolved_by)->toBe($agent->id)
            ->and($issue->resolved_at)->not->toBeNull();
    });

    it('agente responsável remove problema do próprio ticket', function () {
        $agent = actingAsAgent();
        $ticket = tiaTicket($agent);
        $issue = tiaIssue($ticket, $agent);

        $this->deleteJson("/api/v1/tickets/{$ticket->id}/issues/{$issue->id}")
            ->assertOk()
            ->assertJson(['message' => 'Problema removido.']);

        $this->assertDatabaseMissing('ticket_issues', [
            'id' => $issue->id,
        ]);
    });

    it('valida campos obrigatórios na criação e na resolução', function () {
        $agent = actingAsAgent();
        $ticket = tiaTicket($agent);
        $issue = tiaIssue($ticket, $agent);

        $this->postJson("/api/v1/tickets/{$ticket->id}/issues", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $this->patchJson("/api/v1/tickets/{$ticket->id}/issues/{$issue->id}/resolve", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['solution']);
    });

    it('admin consegue resolver problema de ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tiaTicket($owner);
        $issue = tiaIssue($ticket, $owner, ['status' => 'open']);

        $admin = actingAsAdmin();

        $this->patchJson("/api/v1/tickets/{$ticket->id}/issues/{$issue->id}/resolve", [
            'solution' => 'Ajuste aplicado pelo admin.',
        ])->assertOk();

        $issue->refresh();

        expect($issue->status)->toBe('resolved')
            ->and((int) $issue->resolved_by)->toBe($admin->id);
    });
});
