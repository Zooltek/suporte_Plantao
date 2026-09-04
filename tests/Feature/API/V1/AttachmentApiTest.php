<?php

use App\Models\Ticket\Attachment;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function taaTicket(User $owner, array $attributes = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'agent_id' => $owner->id,
        'author_id' => $owner->id,
        'user_id' => $owner->id,
    ], $attributes));
}

function taaAttachment(Ticket $ticket, array $attributes = []): Attachment
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

describe('API V1 — Attachments', function () {
    it('agente responsável lista apenas anexos ativos do próprio ticket', function () {
        $agent = actingAsAgent();
        $ticket = taaTicket($agent);

        taaAttachment($ticket, ['name' => 'visivel.pdf', 'original_name' => 'visivel.pdf']);
        taaAttachment($ticket, [
            'name' => 'oculto.pdf',
            'original_name' => 'oculto.pdf',
            'disk_path' => 'tickets/attachments/oculto.pdf',
            'status' => 0,
        ]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/attachments")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'original_name' => 'visivel.pdf',
                'viewable' => true,
            ]);
    });

    it('agente responsável envia anexo para o próprio ticket', function () {
        $agent = actingAsAgent();
        $ticket = taaTicket($agent);

        $this->withHeader('Accept', 'application/json')
            ->post('/api/v1/attachments', [
                'ticket_id' => $ticket->id,
                'file' => UploadedFile::fake()->create('guia.pdf', 32, 'application/pdf'),
            ])->assertCreated()
            ->assertJsonPath('data.ticket_id', $ticket->id)
            ->assertJsonPath('data.original_name', 'guia.pdf')
            ->assertJsonPath('data.viewable', true);

        $attachment = Attachment::query()->latest('id')->firstOrFail();

        expect($attachment->author_id)->toBe($agent->id)
            ->and($attachment->ticket_id)->toBe($ticket->id)
            ->and($attachment->mime)->toBe('pdf');

        Storage::disk('public')->assertExists($attachment->disk_path);
    });

    it('agente responsável visualiza PDF inline', function () {
        $agent = actingAsAgent();
        $ticket = taaTicket($agent);
        $attachment = taaAttachment($ticket);

        $this->get("/api/v1/attachments/{$attachment->id}/view")
            ->assertOk()
            ->assertHeader('content-disposition', 'inline; filename="manual.pdf"');
    });

    it('agente responsável exclui anexo removendo banco e storage', function () {
        $agent = actingAsAgent();
        $ticket = taaTicket($agent);
        $attachment = taaAttachment($ticket);

        $this->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk()
            ->assertJson(['message' => 'Anexo removido.']);

        $this->assertDatabaseMissing('ticketit_attachments', [
            'id' => $attachment->id,
        ]);
        Storage::disk('public')->assertMissing($attachment->disk_path);
    });

    it('admin visualiza e exclui anexo de qualquer ticket', function () {
        $owner = User::factory()->agent()->create();
        $ticket = taaTicket($owner);
        $attachment = taaAttachment($ticket);

        actingAsAdmin();

        $this->get("/api/v1/attachments/{$attachment->id}/view")
            ->assertOk();

        $this->deleteJson("/api/v1/attachments/{$attachment->id}")
            ->assertOk();
    });
});
