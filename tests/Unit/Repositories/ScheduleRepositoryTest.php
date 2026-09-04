<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\ScheduleRepository;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function sr_schedule(?Company $company = null): Schedule
{
    $company ??= Company::factory()->create();
    $agent   = User::factory()->agent()->create();
    $module  = \App\Models\Schedule\Module::firstOrCreate(['name' => 'Vendas SR'], ['project' => 'EasyMaster']);

    return Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => 'con',
    ]);
}

function sr_insert_record(Schedule $schedule): int
{
    return DB::table('schedule_record')->insertGetId([
        'schedule_id' => $schedule->id,
        'module_id'   => $schedule->module_id,
        'customer_id' => $schedule->customer_id,
        'agent_id'    => $schedule->agent_id,
        'status'      => 1,
        'contact'     => 'Teste',
        'start'       => now()->subHours(3),
        'end'         => now()->subHours(1),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);
}

// ─── save ─────────────────────────────────────────────────────────────────────

describe('ScheduleRepository — save', function () {

    it('persiste um novo agendamento no banco', function () {
        $company  = Company::factory()->create();
        $agent    = User::factory()->agent()->create();
        $module   = \App\Models\Schedule\Module::firstOrCreate(['name' => 'Vendas SR'], ['project' => 'EasyMaster']);

        $schedule              = new Schedule();
        $schedule->customer_id = $company->id;
        $schedule->agent_id    = $agent->id;
        $schedule->module_id   = $module->id;
        $schedule->status      = 'con';
        $schedule->start_at    = now()->addDay();
        $schedule->title       = 'Teste Save';
        $schedule->kind        = 'cli';

        (new ScheduleRepository())->save($schedule);

        expect($schedule->exists)->toBeTrue()
            ->and($schedule->id)->toBeGreaterThan(0);
    });

    it('atualiza um agendamento existente', function () {
        $schedule = sr_schedule();

        $schedule->obs = 'Obs atualizada';

        (new ScheduleRepository())->save($schedule);

        $fresh = Schedule::find($schedule->id);

        expect($fresh->obs)->toBe('Obs atualizada');
    });

    it('persiste mudança de status', function () {
        $schedule = sr_schedule();
        $schedule->status = 'del';

        (new ScheduleRepository())->save($schedule);

        expect(Schedule::find($schedule->id)->status)->toBe('del');
    });

});

// ─── hasActiveRecords ─────────────────────────────────────────────────────────

describe('ScheduleRepository — hasActiveRecords', function () {

    it('retorna true quando há records ativos vinculados', function () {
        $schedule = sr_schedule();
        sr_insert_record($schedule);

        expect((new ScheduleRepository())->hasActiveRecords($schedule->id))->toBeTrue();
    });

    it('retorna false quando não há records vinculados', function () {
        $schedule = sr_schedule();

        expect((new ScheduleRepository())->hasActiveRecords($schedule->id))->toBeFalse();
    });

    it('retorna false quando records existem mas estão inativos', function () {
        $schedule = sr_schedule();

        DB::table('schedule_record')->insert([
            'schedule_id' => $schedule->id,
            'module_id'   => $schedule->module_id,
            'customer_id' => $schedule->customer_id,
            'agent_id'    => $schedule->agent_id,
            'status'      => 0,
            'contact'     => 'Inativo',
            'start'       => now()->subHours(3),
            'end'         => now()->subHours(1),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        expect((new ScheduleRepository())->hasActiveRecords($schedule->id))->toBeFalse();
    });

});

// ─── findTicket ───────────────────────────────────────────────────────────────

describe('ScheduleRepository — findTicket', function () {

    it('retorna o ticket quando existe', function () {
        $ticket = Ticket::factory()->create();

        $result = (new ScheduleRepository())->findTicket($ticket->id);

        expect($result)->not->toBeNull()
            ->and($result->id)->toBe($ticket->id);
    });

    it('retorna null para ticket inexistente', function () {
        $result = (new ScheduleRepository())->findTicket(999999);

        expect($result)->toBeNull();
    });

});
