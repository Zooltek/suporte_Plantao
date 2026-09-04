<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Element;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Repositories\RecordRepository;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function rr_module(): Module
{
    return Module::firstOrCreate(['name' => 'Vendas RC'], ['project' => 'EasyMaster']);
}

function rr_schedule(?Company $company = null): Schedule
{
    $company ??= Company::factory()->create();
    $agent   = User::factory()->agent()->create();
    $module  = rr_module();

    return Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => 'con',
    ]);
}

function rr_validated(int $agentId, int $moduleId, array $overrides = []): array
{
    return array_merge([
        'date'           => '2026-03-15',
        'start_hour'     => '09:00',
        'end_hour'       => '12:00',
        'agent_id'       => $agentId,
        'module_id'      => $moduleId,
        'contact'        => 'Maria Lima',
        'obs'            => 'Observação de teste',
        'version'        => '4.0',
        'release'        => '002',
        'resolved'       => true,
        'interval_start' => '10:00',
        'interval_end'   => '10:30',
    ], $overrides);
}

function rr_insert_record(Schedule $schedule): int
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

// ─── createWithElements ────────────────────────────────────────────────────────

describe('RecordRepository — createWithElements', function () {

    it('persiste o record no banco com os campos escalares corretos', function () {
        $schedule  = rr_schedule();
        $agent     = User::factory()->agent()->create();
        $module    = rr_module();
        $validated = rr_validated($agent->id, $module->id);

        $repo   = new RecordRepository();
        $record = $repo->createWithElements($schedule, $validated, []);

        expect($record)->toBeInstanceOf(Record::class)
            ->and($record->exists)->toBeTrue()
            ->and($record->schedule_id)->toBe($schedule->id)
            ->and($record->module_id)->toBe($module->id)
            ->and($record->agent_id)->toBe($agent->id)
            ->and($record->contact)->toBe('Maria Lima');
    });

    it('converte date + start_hour em Carbon com data e hora corretas', function () {
        $schedule  = rr_schedule();
        $agent     = User::factory()->agent()->create();
        $module    = rr_module();
        $validated = rr_validated($agent->id, $module->id, [
            'date'       => '2026-04-10',
            'start_hour' => '08:30',
            'end_hour'   => '11:00',
        ]);

        $record = (new RecordRepository())->createWithElements($schedule, $validated, []);

        expect($record->start->format('Y-m-d H:i'))->toBe('2026-04-10 08:30')
            ->and($record->end->format('Y-m-d H:i'))->toBe('2026-04-10 11:00');
    });

    it('não persiste intervalos quando interval_start está vazio', function () {
        $schedule  = rr_schedule();
        $agent     = User::factory()->agent()->create();
        $module    = rr_module();
        $validated = rr_validated($agent->id, $module->id, [
            'interval_start' => '',
            'interval_end'   => '',
        ]);

        $record = (new RecordRepository())->createWithElements($schedule, $validated, []);

        expect($record->interval_start)->toBeNull()
            ->and($record->interval_end)->toBeNull();
    });

    it('salva elementos dinâmicos presentes no rawInput', function () {
        $module = rr_module();
        $group  = ElementGroup::firstOrCreate(['name' => 'Geral']);
        $type   = ElementType::firstOrCreate(
            ['name' => 'apres_menus'],
            ['label' => 'Apresentação dos Menus', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $schedule  = rr_schedule();
        $agent     = User::factory()->agent()->create();
        $validated = rr_validated($agent->id, $module->id);
        $rawInput  = ['apres_menus' => '1'];

        $record = (new RecordRepository())->createWithElements($schedule, $validated, $rawInput);

        expect(Element::where('record_id', $record->id)->where('element_id', $type->id)->exists())->toBeTrue();
    });

    it('não cria elementos quando rawInput está vazio', function () {
        $schedule  = rr_schedule();
        $agent     = User::factory()->agent()->create();
        $module    = rr_module();
        $validated = rr_validated($agent->id, $module->id);

        $record = (new RecordRepository())->createWithElements($schedule, $validated, []);

        expect(Element::where('record_id', $record->id)->count())->toBe(0);
    });

});

// ─── findActiveForPrint ────────────────────────────────────────────────────────

describe('RecordRepository — findActiveForPrint', function () {

    it('retorna o record ativo pelo id com relações carregadas', function () {
        $company  = Company::factory()->create();
        $schedule = rr_schedule($company);
        $id       = rr_insert_record($schedule);

        $record = (new RecordRepository())->findActiveForPrint($id);

        expect($record)->toBeInstanceOf(Record::class)
            ->and($record->id)->toBe($id)
            ->and($record->relationLoaded('schedule'))->toBeTrue();
    });

    it('lança ModelNotFoundException para record inativo', function () {
        $company  = Company::factory()->create();
        $schedule = rr_schedule($company);

        $id = DB::table('schedule_record')->insertGetId([
            'schedule_id' => $schedule->id,
            'module_id'   => $schedule->module_id,
            'customer_id' => $company->id,
            'agent_id'    => $schedule->agent_id,
            'status'      => 0,
            'contact'     => 'Teste',
            'start'       => now()->subHours(3),
            'end'         => now()->subHours(1),
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        expect(fn () => (new RecordRepository())->findActiveForPrint($id))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── getElementTypesByModule & getRecordElementsKeyed ─────────────────────────

describe('RecordRepository — getElementTypesByModule', function () {

    it('retorna apenas os ElementTypes do módulo solicitado', function () {
        $module = rr_module();
        $group  = ElementGroup::firstOrCreate(['name' => 'Geral']);
        ElementType::firstOrCreate(
            ['name' => 'tipo_teste_rr'],
            ['label' => 'Tipo Teste RR', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $result = (new RecordRepository())->getElementTypesByModule($module->id);

        expect($result->pluck('module_id')->unique()->toArray())->toContain($module->id);
    });

    it('carrega a relação group em cada ElementType', function () {
        $module = rr_module();
        $group  = ElementGroup::firstOrCreate(['name' => 'Geral']);
        ElementType::firstOrCreate(
            ['name' => 'tipo_teste_rr2'],
            ['label' => 'Tipo Teste RR2', 'type' => 'text', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $result = (new RecordRepository())->getElementTypesByModule($module->id);

        expect($result->first()->relationLoaded('group'))->toBeTrue();
    });

    it('retorna coleção indexada por element_id para getRecordElementsKeyed', function () {
        $company  = Company::factory()->create();
        $module   = rr_module();
        $schedule = rr_schedule($company);
        $id       = rr_insert_record($schedule);
        $group    = ElementGroup::firstOrCreate(['name' => 'Geral']);
        $type     = ElementType::firstOrCreate(
            ['name' => 'tipo_keyed'],
            ['label' => 'Tipo Keyed', 'type' => 'text', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        DB::table('schedule_record_element')->insert([
            'record_id'  => $id,
            'element_id' => $type->id,
            'value'      => 'sim',
        ]);

        $result = (new RecordRepository())->getRecordElementsKeyed($id);

        expect($result)->toHaveKey($type->id);
    });

});
