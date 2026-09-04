<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketIssue;
use App\Models\User;
use App\Repositories\TicketIssueRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tir_repo(): TicketIssueRepository
{
    return new TicketIssueRepository();
}

function tir_ticket(): Ticket
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

function tir_issue(Ticket $ticket, array $attrs = []): TicketIssue
{
    $user = User::factory()->agent()->create();

    return TicketIssue::create(array_merge([
        'ticket_id'   => $ticket->id,
        'created_by'  => $user->id,
        'title'       => 'Issue de Teste',
        'description' => 'Descrição do problema',
        'status'      => 'open',
    ], $attrs));
}

// ─── allForTicket ─────────────────────────────────────────────────────────────

describe('TicketIssueRepository — allForTicket', function () {

    it('retorna issues apenas do ticket especificado', function () {
        $ticketA = tir_ticket();
        $ticketB = tir_ticket();

        $issueA = tir_issue($ticketA);
        tir_issue($ticketB);

        $result = tir_repo()->allForTicket($ticketA->id);

        expect($result->count())->toBe(1);
        expect($result->first()->id)->toBe($issueA->id);
    });

    it('retorna em ordem crescente de created_at', function () {
        $ticket = tir_ticket();

        $older = tir_issue($ticket, ['created_at' => now()->subHour()]);
        $newer = tir_issue($ticket, ['created_at' => now()]);

        $result = tir_repo()->allForTicket($ticket->id);

        expect($result->first()->id)->toBe($older->id);
        expect($result->last()->id)->toBe($newer->id);
    });

    it('carrega as relações creator e resolver', function () {
        $ticket = tir_ticket();
        tir_issue($ticket);

        $result = tir_repo()->allForTicket($ticket->id);

        $issue = $result->first();
        expect($issue->relationLoaded('creator'))->toBeTrue();
        expect($issue->relationLoaded('resolver'))->toBeTrue();
    });

    it('retorna coleção vazia quando não há issues', function () {
        $ticket = tir_ticket();

        $result = tir_repo()->allForTicket($ticket->id);

        expect($result->isEmpty())->toBeTrue();
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('TicketIssueRepository — create', function () {

    it('persiste a issue no banco com status open', function () {
        $ticket = tir_ticket();
        $user   = User::factory()->agent()->create();

        $issue = tir_repo()->create([
            'ticket_id'   => $ticket->id,
            'created_by'  => $user->id,
            'title'       => 'Problema crítico',
            'description' => 'Detalhe do problema',
            'status'      => 'open',
        ]);

        expect($issue)->toBeInstanceOf(TicketIssue::class);
        expect($issue->exists)->toBeTrue();

        test()->assertDatabaseHas('ticket_issues', [
            'id'        => $issue->id,
            'title'     => 'Problema crítico',
            'status'    => 'open',
            'ticket_id' => $ticket->id,
        ]);
    });

});

// ─── update ───────────────────────────────────────────────────────────────────

describe('TicketIssueRepository — update', function () {

    it('atualiza o status e a solução da issue', function () {
        $ticket  = tir_ticket();
        $issue   = tir_issue($ticket);
        $resolver = User::factory()->agent()->create();

        $updated = tir_repo()->update($issue, [
            'status'      => 'resolved',
            'solution'    => 'Problema resolvido via reinicialização.',
            'resolved_at' => now(),
            'resolved_by' => $resolver->id,
        ]);

        expect($updated->status)->toBe('resolved');
        expect($updated->solution)->toBe('Problema resolvido via reinicialização.');
        expect($updated->resolved_by)->toBe($resolver->id);

        test()->assertDatabaseHas('ticket_issues', [
            'id'     => $issue->id,
            'status' => 'resolved',
        ]);
    });

    it('retorna a issue com as relações creator e resolver carregadas', function () {
        $ticket  = tir_ticket();
        $issue   = tir_issue($ticket);
        $resolver = User::factory()->agent()->create();

        $updated = tir_repo()->update($issue, [
            'status'      => 'resolved',
            'solution'    => 'Resolvido',
            'resolved_at' => now(),
            'resolved_by' => $resolver->id,
        ]);

        expect($updated->relationLoaded('creator'))->toBeTrue();
        expect($updated->relationLoaded('resolver'))->toBeTrue();
    });

});

// ─── delete ───────────────────────────────────────────────────────────────────

describe('TicketIssueRepository — delete', function () {

    it('remove a issue do banco', function () {
        $ticket = tir_ticket();
        $issue  = tir_issue($ticket);
        $id     = $issue->id;

        tir_repo()->delete($issue);

        test()->assertDatabaseMissing('ticket_issues', ['id' => $id]);
    });

});
