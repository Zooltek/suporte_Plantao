<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Attachment;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\AttachmentRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ar_repo(): AttachmentRepository
{
    return new AttachmentRepository();
}

function ar_ticket(): Ticket
{
    $user    = User::factory()->agent()->create();
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $cat     = Category::factory()->create(['parent_id' => 0]);

    return Ticket::factory()->create([
        'user_id'     => $user->id,
        'company_id'  => $company->id,
        'category_id' => $cat->category_id,
        'status_id'   => Ticket::STATUS_PENDING_ID,
        'priority_id' => 1,
    ]);
}

function ar_attachment(array $attrs = []): Attachment
{
    $user   = User::factory()->agent()->create();
    $ticket = ar_ticket();

    return Attachment::create(array_merge([
        'name'          => 'test-file.pdf',
        'original_name' => 'original.pdf',
        'mime'          => 'pdf',
        'disk_path'     => 'tickets/attachments/test-file.pdf',
        'size'          => 1024,
        'author_id'     => $user->id,
        'ticket_id'     => $ticket->id,
        'status'        => 1,
    ], $attrs));
}

// ─── create ───────────────────────────────────────────────────────────────────

describe('AttachmentRepository — create', function () {

    it('persiste o attachment no banco', function () {
        $ticket = ar_ticket();
        $user   = User::factory()->agent()->create();

        $attachment = ar_repo()->create([
            'name'          => 'uuid-test.pdf',
            'original_name' => 'meu-arquivo.pdf',
            'mime'          => 'pdf',
            'disk_path'     => 'tickets/attachments/uuid-test.pdf',
            'size'          => 2048,
            'author_id'     => $user->id,
            'ticket_id'     => $ticket->id,
            'status'        => 1,
        ]);

        expect($attachment)->toBeInstanceOf(Attachment::class);
        expect($attachment->exists)->toBeTrue();

        test()->assertDatabaseHas('ticketit_attachments', [
            'id'            => $attachment->id,
            'original_name' => 'meu-arquivo.pdf',
        ]);
    });

    it('associa o attachment ao ticket quando ticket_id é fornecido', function () {
        $ticket     = ar_ticket();
        $user       = User::factory()->agent()->create();

        $attachment = ar_repo()->create([
            'name'          => 'for-ticket.pdf',
            'original_name' => 'for-ticket.pdf',
            'mime'          => 'pdf',
            'disk_path'     => 'tickets/attachments/for-ticket.pdf',
            'size'          => 512,
            'author_id'     => $user->id,
            'ticket_id'     => $ticket->id,
            'status'        => 1,
        ]);

        expect($attachment->ticket_id)->toBe($ticket->id);
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('AttachmentRepository — delete', function () {

    it('remove o attachment do banco', function () {
        $attachment = ar_attachment();
        $id         = $attachment->id;

        ar_repo()->delete($attachment);

        test()->assertDatabaseMissing('ticketit_attachments', ['id' => $id]);
    });

});

// ─── findById ─────────────────────────────────────────────────────────────────

describe('AttachmentRepository — findById', function () {

    it('retorna o attachment pelo id', function () {
        $attachment = ar_attachment();

        $found = ar_repo()->findById($attachment->id);

        expect($found)->not->toBeNull();
        expect($found->id)->toBe($attachment->id);
    });

    it('retorna null quando o id não existe', function () {
        $found = ar_repo()->findById(999999);

        expect($found)->toBeNull();
    });

});

// ─── findByTicket ─────────────────────────────────────────────────────────────

describe('AttachmentRepository — findByTicket', function () {

    it('retorna attachments de um ticket específico', function () {
        $ticket  = ar_ticket();
        $other   = ar_ticket();

        $attA = ar_attachment(['ticket_id' => $ticket->id]);
        $attB = ar_attachment(['ticket_id' => $ticket->id]);
        ar_attachment(['ticket_id' => $other->id]);

        $result = ar_repo()->findByTicket($ticket->id);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($attA->id);
        expect($ids)->toContain($attB->id);
        expect(count($ids))->toBe(2);
    });

    it('retorna coleção vazia quando não há attachments', function () {
        $ticket = ar_ticket();

        $result = ar_repo()->findByTicket($ticket->id);

        expect($result->isEmpty())->toBeTrue();
    });

});
