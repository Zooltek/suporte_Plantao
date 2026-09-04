<?php

/**
 * Testes de integração — AttendanceRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\AttendanceRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function att_repo(): AttendanceRepository
{
    return new AttendanceRepository();
}

function att_ticket(): Ticket
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

function att_user(): User
{
    return User::factory()->agent()->create();
}

// ─── findTicketOrFail ─────────────────────────────────────────────────────────

describe('AttendanceRepository — findTicketOrFail', function () {

    it('retorna o ticket quando o id existe', function () {
        $ticket = att_ticket();

        $found = att_repo()->findTicketOrFail($ticket->id);

        expect($found)->toBeInstanceOf(Ticket::class);
        expect($found->id)->toBe($ticket->id);
    });

    it('lança ModelNotFoundException quando o id não existe', function () {
        att_repo()->findTicketOrFail(999999);
    })->throws(ModelNotFoundException::class);

});

// ─── listForTicket ────────────────────────────────────────────────────────────

describe('AttendanceRepository — listForTicket', function () {

    it('retorna atendimentos do ticket ordenados do mais recente', function () {
        $ticket = att_ticket();
        $user   = att_user();

        \Illuminate\Support\Facades\DB::table('attendances')->insert([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'notes'      => 'Primeiro atendimento',
            'return_zap' => 0,
            'return_tel' => 0,
            'return_cel' => 0,
            'returned_by' => null,
            'returned_at' => null,
            'created_at' => '2024-01-01 08:00:00',
            'updated_at' => '2024-01-01 08:00:00',
        ]);
        $att1 = Attendance::where('notes', 'Primeiro atendimento')->first();

        \Illuminate\Support\Facades\DB::table('attendances')->insert([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'notes'      => 'Segundo atendimento',
            'return_zap' => 0,
            'return_tel' => 0,
            'return_cel' => 0,
            'returned_by' => null,
            'returned_at' => null,
            'created_at' => '2025-06-01 08:00:00',
            'updated_at' => '2025-06-01 08:00:00',
        ]);
        $att2 = Attendance::where('notes', 'Segundo atendimento')->first();

        $result = att_repo()->listForTicket($ticket->id);

        expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
        expect($result->count())->toBe(2);
        // Deve estar ordenado do mais recente (att2, de 2025, deve vir primeiro)
        expect($result->first()->id)->toBe($att2->id);
    });

    it('retorna coleção vazia quando não há atendimentos', function () {
        $ticket = att_ticket();

        $result = att_repo()->listForTicket($ticket->id);

        expect($result->isEmpty())->toBeTrue();
    });

    it('lança ModelNotFoundException para ticket inexistente', function () {
        att_repo()->listForTicket(999999);
    })->throws(ModelNotFoundException::class);

});

// ─── create ───────────────────────────────────────────────────────────────────

describe('AttendanceRepository — create', function () {

    it('persiste o atendimento no banco', function () {
        $ticket = att_ticket();
        $user   = att_user();

        $attendance = att_repo()->create([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'notes'      => 'Teste de criação',
            'return_zap' => false,
            'return_tel' => false,
            'return_cel' => false,
            'returned_by' => null,
            'returned_at' => null,
        ]);

        expect($attendance)->toBeInstanceOf(Attendance::class);
        expect($attendance->exists)->toBeTrue();
        expect($attendance->ticket_id)->toBe($ticket->id);
        expect($attendance->user_id)->toBe($user->id);

        test()->assertDatabaseHas('attendances', [
            'id'        => $attendance->id,
            'ticket_id' => $ticket->id,
            'notes'     => 'Teste de criação',
        ]);
    });

    it('cria atendimento com via de retorno', function () {
        $ticket   = att_ticket();
        $user     = att_user();
        $assignee = att_user();

        $attendance = att_repo()->create([
            'ticket_id'           => $ticket->id,
            'user_id'             => $user->id,
            'notes'               => 'Com retorno',
            'return_zap'          => true,
            'return_tel'          => false,
            'return_cel'          => false,
            'return_assigned_to'  => $assignee->id,
            'return_scheduled_at' => now()->addDay(),
            'returned_by'         => null,
            'returned_at'         => null,
        ]);

        expect($attendance->return_zap)->toBeTrue();
        expect($attendance->return_assigned_to)->toBe($assignee->id);
    });

});
