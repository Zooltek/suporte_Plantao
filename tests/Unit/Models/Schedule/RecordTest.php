<?php

use App\Models\Schedule\Record;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

describe('Record::total_minutes', function () {
    it('calcula minutos líquidos sem intervalo', function () {
        $record = new Record([
            'start' => Carbon::parse('2026-03-13 09:00:00'),
            'end'   => Carbon::parse('2026-03-13 12:00:00'),
        ]);

        expect($record->total_minutes)->toBe(180);
    });

    it('desconta o intervalo do total', function () {
        $record = new Record([
            'start'          => Carbon::parse('2026-03-13 09:00:00'),
            'end'            => Carbon::parse('2026-03-13 13:00:00'),
            'interval_start' => Carbon::parse('2026-03-13 12:00:00'),
            'interval_end'   => Carbon::parse('2026-03-13 13:00:00'),
        ]);

        // 4h total - 1h intervalo = 3h = 180 min
        expect($record->total_minutes)->toBe(180);
    });

    it('retorna zero quando start ou end é nulo', function () {
        $record = new Record(['start' => null, 'end' => null]);

        expect($record->total_minutes)->toBe(0);
    });

    it('nunca retorna valor negativo', function () {
        // Caso edge: fim antes do início (dado inválido)
        $record = new Record([
            'start' => Carbon::parse('2026-03-13 12:00:00'),
            'end'   => Carbon::parse('2026-03-13 09:00:00'),
        ]);

        expect($record->total_minutes)->toBeGreaterThanOrEqual(0);
    });

    it('ignora intervalo quando apenas interval_start está preenchido', function () {
        $record = new Record([
            'start'          => Carbon::parse('2026-03-13 09:00:00'),
            'end'            => Carbon::parse('2026-03-13 12:00:00'),
            'interval_start' => Carbon::parse('2026-03-13 11:00:00'),
            'interval_end'   => null,
        ]);

        // Sem interval_end, não desconta nada
        expect($record->total_minutes)->toBe(180);
    });
});

// ─────────────────────────────────────────────
// Scope active()
// ─────────────────────────────────────────────
describe('Record — scope active()', function () {

    it('retorna apenas records com status = 1', function () {
        $agent   = actingAsAgent();
        $module  = Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);
        $company = Company::factory()->create();
        $schedule = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
        ]);

        DB::table('schedule_record')->insert([
            ['schedule_id' => $schedule->id, 'module_id' => $module->id, 'customer_id' => $company->id,
             'agent_id' => $agent->id, 'status' => 1, 'contact' => 'A',
             'start' => now()->subHours(3), 'end' => now()->subHours(1),
             'created_at' => now(), 'updated_at' => now()],
            ['schedule_id' => $schedule->id, 'module_id' => $module->id, 'customer_id' => $company->id,
             'agent_id' => $agent->id, 'status' => 0, 'contact' => 'B',
             'start' => now()->subHours(3), 'end' => now()->subHours(1),
             'created_at' => now(), 'updated_at' => now()],
        ]);

        expect(Record::active()->count())->toBe(1);
    });

    it('exclui records com status = 0', function () {
        $agent   = actingAsAgent();
        $module  = Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);
        $company = Company::factory()->create();
        $schedule = Schedule::factory()->create([
            'customer_id' => $company->id,
            'agent_id'    => $agent->id,
            'module_id'   => $module->id,
            'status'      => 'con',
        ]);

        DB::table('schedule_record')->insert([
            'schedule_id' => $schedule->id, 'module_id' => $module->id, 'customer_id' => $company->id,
            'agent_id' => $agent->id, 'status' => 0, 'contact' => 'C',
            'start' => now()->subHours(3), 'end' => now()->subHours(1),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        expect(Record::active()->count())->toBe(0);
    });

});

// ─────────────────────────────────────────────
// Relacionamentos
// ─────────────────────────────────────────────
describe('Record — relacionamentos', function () {

    it('module() retorna instância de BelongsTo', function () {
        expect((new Record())->module())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('elements() retorna instância de HasMany', function () {
        expect((new Record())->elements())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
    });

    it('schedule() retorna instância de BelongsTo', function () {
        expect((new Record())->schedule())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('customer() retorna instância de BelongsTo', function () {
        expect((new Record())->customer())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

    it('agent() retorna instância de BelongsTo', function () {
        expect((new Record())->agent())
            ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    });

});
