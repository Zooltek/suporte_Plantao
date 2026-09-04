<?php

use App\Models\Ticket\Attachment;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketAudit;
use App\Models\Ticket\TicketIssue;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function tacTicket(User $owner, array $attributes = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'agent_id' => $owner->id,
        'author_id' => $owner->id,
        'user_id' => $owner->id,
    ], $attributes));
}

function tacIssue(Ticket $ticket, User $creator, array $attributes = []): TicketIssue
{
    return TicketIssue::factory()->create(array_merge([
        'ticket_id' => $ticket->id,
        'created_by' => $creator->id,
    ], $attributes));
}

function tacAttachment(Ticket $ticket, array $attributes = []): Attachment
{
    $data = array_merge([
        'name' => 'manual.pdf',
        'original_name' => 'manual.pdf',
        'disk_path' => 'tickets/attachments/manual.pdf',
        'size' => 12,
        'mime' => 'pdf',
        'author_id' => $ticket->agent_id,
        'ticket_id' => $ticket->id,
        'status' => 1,
    ], $attributes);

    Storage::disk('public')->put($data['disk_path'], 'conteudo-teste');

    return Attachment::query()->create($data);
}

beforeEach(function () {
    Storage::fake('public');
});

describe('API V1 — Ticket access control', function () {
    it('agente consegue listar atendimentos de ticket de outro agente quando tem acesso de visualização ao chamado', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);
        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
        ]);

        actingAsAgent();

        $this->getJson("/api/v1/tickets/{$ticket->id}/attendances")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    });

    it('agente não consegue criar atendimento em ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);

        actingAsAgent();

        $this->postJson("/api/v1/tickets/{$ticket->id}/attendances", [
            'notes' => 'Tentativa indevida',
        ])->assertForbidden();

        $this->assertDatabaseMissing('attendances', [
            'ticket_id' => $ticket->id,
            'notes' => 'Tentativa indevida',
        ]);
    });

    it('agente não consegue listar auditorias de ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);

        TicketAudit::query()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $owner->id,
            'event' => 'updated',
            'operation' => 'update',
            'field' => 'status_id',
            'old_value' => '1',
            'new_value' => '2',
        ]);

        actingAsAgent();

        $this->getJson("/api/v1/tickets/{$ticket->id}/audits")
            ->assertForbidden();
    });

    it('agente não consegue listar ou criar problemas em ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);
        tacIssue($ticket, $owner);

        actingAsAgent();

        $this->getJson("/api/v1/tickets/{$ticket->id}/issues")
            ->assertForbidden();

        $this->postJson("/api/v1/tickets/{$ticket->id}/issues", [
            'title' => 'Tentativa indevida',
        ])->assertForbidden();
    });

    it('impede resolver problema com ticket divergente da rota', function () {
        $attacker = actingAsAgent();
        $ownedTicket = tacTicket($attacker);

        $owner = User::factory()->agent()->create();
        $foreignTicket = tacTicket($owner);
        $issue = tacIssue($foreignTicket, $owner);

        $this->patchJson("/api/v1/tickets/{$ownedTicket->id}/issues/{$issue->id}/resolve", [
            'solution' => 'Ajuste malicioso',
        ])->assertNotFound();
    });

    it('agente não consegue listar ou enviar anexos para ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);
        tacAttachment($ticket);

        actingAsAgent();

        $this->getJson("/api/v1/tickets/{$ticket->id}/attachments")
            ->assertForbidden();

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/attachments', [
                'ticket_id' => $ticket->id,
                'file' => UploadedFile::fake()->create('manual.pdf', 32, 'application/pdf'),
            ])->assertForbidden();
    });

    it('agente não consegue visualizar ou excluir anexo de ticket de outro agente', function () {
        $owner = User::factory()->agent()->create();
        $ticket = tacTicket($owner);
        $attachment = tacAttachment($ticket);

        actingAsAgent();

        $this->get("/api/v1/attachments/{$attachment->id}/view")
            ->assertForbidden();

        $this->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertForbidden();
    });
});
