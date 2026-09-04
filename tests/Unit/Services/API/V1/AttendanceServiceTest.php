<?php

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\API\V1\Tickets\AttendanceService;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function as_ticket(): Ticket
{
    return Ticket::factory()->create();
}

// ─── Testes ───────────────────────────────────────────────────────────────────

describe('AttendanceService — create', function () {

    beforeEach(function () {
        $this->user    = User::factory()->agent()->create();
        $this->ticket  = as_ticket();
        $this->service = app(AttendanceService::class);
        test()->actingAs($this->user, 'admin');
    });

    it('cria atendimento com notes e sem retorno', function () {
        $attendance = $this->service->create($this->ticket->id, [
            'notes' => 'Verificado o problema.',
        ]);

        expect($attendance)->toBeInstanceOf(Attendance::class)
            ->and($attendance->notes)->toBe('Verificado o problema.')
            ->and($attendance->ticket_id)->toBe($this->ticket->id)
            ->and($attendance->user_id)->toBe($this->user->id);
    });

    it('com via de retorno sem agendamento registra retorno realizado', function () {
        $attendance = $this->service->create($this->ticket->id, [
            'notes'      => 'Nota qualquer',
            'return_zap' => true,
        ]);

        expect($attendance->returned_by)->toBe($this->user->id)
            ->and($attendance->returned_at)->not->toBeNull()
            ->and($attendance->return_scheduled_at)->toBeNull()
            ->and($attendance->return_assigned_to)->toBeNull();
    });

    it('sem via de retorno, return_assigned_to é nulo', function () {
        $attendance = $this->service->create($this->ticket->id, [
            'notes'      => 'Sem retorno',
            'return_zap' => false,
            'return_tel' => false,
            'return_cel' => false,
        ]);

        expect($attendance->return_assigned_to)->toBeNull()
            ->and($attendance->return_scheduled_at)->toBeNull();
    });

    it('com via de retorno e dados de agendamento, return_assigned_to recebe o user_id informado', function () {
        $assignee = User::factory()->agent()->create();

        $attendance = $this->service->create($this->ticket->id, [
            'notes'           => 'Agendado retorno',
            'return_zap'      => true,
            'return_user_id'  => $assignee->id,
            'return_at'       => now()->addDay()->toDateTimeString(),
        ]);

        expect($attendance->return_assigned_to)->toBe($assignee->id)
            ->and($attendance->return_scheduled_at)->not->toBeNull()
            ->and($attendance->returned_by)->toBeNull()
            ->and($attendance->returned_at)->toBeNull();
    });

    it('com via de retorno mas sem metadados de agendamento, não cria agendamento implícito', function () {
        $attendance = $this->service->create($this->ticket->id, [
            'notes'      => 'Retorno sem responsável',
            'return_tel' => true,
        ]);

        expect($attendance->return_assigned_to)->toBeNull()
            ->and($attendance->return_scheduled_at)->toBeNull()
            ->and($attendance->returned_at)->not->toBeNull();
    });

    it('com retorno agendado e sem return_at, scheduled_at recebe now()', function () {
        $assignee = User::factory()->agent()->create();

        $attendance = $this->service->create($this->ticket->id, [
            'notes'      => 'Retorno imediato',
            'return_cel' => true,
            'return_user_id' => $assignee->id,
        ]);

        expect($attendance->return_scheduled_at)->not->toBeNull()
            ->and($attendance->return_assigned_to)->toBe($assignee->id)
            ->and($attendance->returned_at)->toBeNull();
    });

    it('persiste os flags de via de retorno corretamente', function () {
        $attendance = $this->service->create($this->ticket->id, [
            'notes'      => 'Múltiplas vias',
            'return_zap' => true,
            'return_tel' => true,
            'return_cel' => false,
        ]);

        expect($attendance->return_zap)->toBeTrue()
            ->and($attendance->return_tel)->toBeTrue()
            ->and($attendance->return_cel)->toBeFalse();
    });

    it('listForTicket retorna atendimentos do ticket em ordem decrescente', function () {
        Attendance::factory()->create([
            'ticket_id'  => $this->ticket->id,
            'user_id'    => $this->user->id,
            'created_at' => now()->subHour(),
        ]);
        Attendance::factory()->create([
            'ticket_id'  => $this->ticket->id,
            'user_id'    => $this->user->id,
            'created_at' => now(),
        ]);

        $list = $this->service->listForTicket($this->ticket->id);

        expect($list)->toHaveCount(2)
            ->and($list->first()->created_at->gt($list->last()->created_at))->toBeTrue();
    });

    it('listForTicket não retorna atendimentos de outro ticket', function () {
        $other = as_ticket();
        Attendance::factory()->create(['ticket_id' => $other->id, 'user_id' => $this->user->id]);

        $list = $this->service->listForTicket($this->ticket->id);

        expect($list)->toHaveCount(0);
    });

});
