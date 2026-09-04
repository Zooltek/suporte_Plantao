<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketAudit;
use App\Models\User;
use App\Repositories\TicketAuditRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tar_repo(): TicketAuditRepository
{
    return new TicketAuditRepository();
}

function tar_ticket(): Ticket
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

function tar_audit(Ticket $ticket, User $user, array $attrs = []): TicketAudit
{
    return TicketAudit::create(array_merge([
        'ticket_id' => $ticket->id,
        'user_id'   => $user->id,
        'operation' => 'Status alterado de Aberto para Em Andamento',
        'event'     => 'updated',
        'field'     => 'status_id',
        'old_value' => '1',
        'new_value' => '2',
    ], $attrs));
}

// ─── allForTicket ─────────────────────────────────────────────────────────────

describe('TicketAuditRepository — allForTicket', function () {

    it('retorna auditorias apenas do ticket especificado', function () {
        $ticketA = tar_ticket();
        $ticketB = tar_ticket();
        $user    = User::factory()->agent()->create();

        // TicketObserver cria audits automaticamente na criação do ticket.
        // Contamos os existentes antes de criar manuais.
        $existingCountA = TicketAudit::where('ticket_id', $ticketA->id)->count();
        $existingCountB = TicketAudit::where('ticket_id', $ticketB->id)->count();

        $auditA = tar_audit($ticketA, $user);
        tar_audit($ticketB, $user);

        $result = tar_repo()->allForTicket($ticketA->id);

        $ids = $result->pluck('id')->toArray();
        expect($ids)->toContain($auditA->id);
        // Resultado deve conter apenas audits do ticketA
        $ticketBIds = TicketAudit::where('ticket_id', $ticketB->id)->pluck('id')->toArray();
        foreach ($ticketBIds as $id) {
            expect($ids)->not->toContain($id);
        }
        expect($result->count())->toBe($existingCountA + 1);
    });

    it('retorna em ordem decrescente de created_at', function () {
        $ticket = tar_ticket();
        $user   = User::factory()->agent()->create();

        // Cria dois audits; depois força updated_at via DB para simular ordenação.
        $older = tar_audit($ticket, $user);
        $newer = tar_audit($ticket, $user);

        // Força o 'older' para um timestamp muito no passado via atualização direta.
        \Illuminate\Support\Facades\DB::table('ticketit_audits')
            ->where('id', $older->id)
            ->update(['created_at' => '2010-01-01 00:00:00']);

        // Força o 'newer' para um timestamp muito no futuro.
        \Illuminate\Support\Facades\DB::table('ticketit_audits')
            ->where('id', $newer->id)
            ->update(['created_at' => '2099-12-31 23:59:59']);

        $result = tar_repo()->allForTicket($ticket->id);

        $positions = $result->pluck('id')->toArray();
        $newerPos  = array_search($newer->id, $positions);
        $olderPos  = array_search($older->id, $positions);

        // O mais recente (2099) deve aparecer antes do mais antigo (2010)
        expect($newerPos)->toBeLessThan($olderPos);
    });

    it('carrega a relação user com eager loading', function () {
        $ticket = tar_ticket();
        $user   = User::factory()->agent()->create();

        tar_audit($ticket, $user);

        $result = tar_repo()->allForTicket($ticket->id);

        expect($result->first()->relationLoaded('user'))->toBeTrue();
    });

    it('retorna apenas audits do ticket correto quando outros tickets existem', function () {
        $ticketA = tar_ticket();
        $ticketB = tar_ticket();

        $resultA = tar_repo()->allForTicket($ticketA->id);
        $resultB = tar_repo()->allForTicket($ticketB->id);

        $idsA = $resultA->pluck('id')->toArray();
        $idsB = $resultB->pluck('id')->toArray();

        // Não deve haver sobreposição de IDs entre os dois tickets
        expect(array_intersect($idsA, $idsB))->toBe([]);
    });

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('TicketAuditRepository — create', function () {

    it('persiste a auditoria no banco', function () {
        $ticket = tar_ticket();
        $user   = User::factory()->agent()->create();

        $audit = tar_repo()->create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'operation' => 'Ticket criado',
            'event'     => 'created',
            'field'     => null,
            'old_value' => null,
            'new_value' => null,
        ]);

        expect($audit)->toBeInstanceOf(TicketAudit::class);
        expect($audit->exists)->toBeTrue();

        test()->assertDatabaseHas('ticketit_audits', [
            'id'        => $audit->id,
            'ticket_id' => $ticket->id,
            'event'     => 'created',
        ]);
    });

    it('retorna uma instância de TicketAudit', function () {
        $ticket = tar_ticket();
        $user   = User::factory()->agent()->create();

        $audit = tar_repo()->create([
            'ticket_id' => $ticket->id,
            'user_id'   => $user->id,
            'operation' => 'Campo alterado',
            'event'     => 'updated',
        ]);

        expect($audit)->toBeInstanceOf(TicketAudit::class);
    });

});
