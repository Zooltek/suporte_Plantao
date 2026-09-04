<?php

use App\Jobs\Agent\Schedule\SyncRecord;
use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Element;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Services\Agent\RecordService;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

// ─── Helpers locais ───────────────────────────────────────────────────────────

function rsModule(string $name = 'Vendas RC'): Module
{
    return Module::firstOrCreate(['name' => $name], ['project' => 'EasyMaster']);
}

function rsSchedule(?Company $company = null): Schedule
{
    $company ??= Company::factory()->create();
    $agent   = User::factory()->agent()->create();
    $module  = rsModule();

    return Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => 'con',
    ]);
}

function rsValidated(int $agentId, int $moduleId, array $overrides = []): array
{
    return array_merge([
        'date'           => '2026-03-15',
        'start_hour'     => '09:00',
        'end_hour'       => '12:00',
        'agent_id'       => $agentId,
        'module_id'      => $moduleId,
        'contact'        => 'João Silva',
        'obs'            => 'Observação de teste',
        'version'        => '3.5',
        'release'        => '001',
        'resolved'       => true,
        'has_nfe'        => false,
        'interval_start' => '10:00',
        'interval_end'   => '10:30',
    ], $overrides);
}

function rsInsertRecord(Schedule $schedule): int
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

// ─── createRecord() ───────────────────────────────────────────────────────────

describe('RecordService — createRecord()', function () {

    it('persiste o record no banco com os campos escalares corretos', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record)->toBeInstanceOf(Record::class)
            ->and($record->exists)->toBeTrue()
            ->and($record->schedule_id)->toBe($schedule->id)
            ->and($record->module_id)->toBe($module->id)
            ->and($record->agent_id)->toBe($agent->id)
            ->and($record->contact)->toBe('João Silva')
            ->and($record->obs)->toBe('Observação de teste')
            ->and($record->version)->toBe('3.5')
            ->and($record->release)->toBe('001');
    });

    it('persiste resolved como booleano', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id, ['resolved' => true]);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record->resolved)->toBeTrue();
    });

    it('converte date + start_hour/end_hour em Carbon com data correta', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id, [
            'date'       => '2026-03-15',
            'start_hour' => '09:00',
            'end_hour'   => '17:00',
        ]);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record->start->format('Y-m-d H:i'))->toBe('2026-03-15 09:00')
            ->and($record->end->format('Y-m-d H:i'))->toBe('2026-03-15 17:00');
    });

    it('persiste interval_start e interval_end quando ambos são informados', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id, [
            'interval_start' => '12:00',
            'interval_end'   => '13:00',
        ]);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record->interval_start->format('H:i'))->toBe('12:00')
            ->and($record->interval_end->format('H:i'))->toBe('13:00');
    });

    it('não persiste intervalos quando interval_start está vazio', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id, [
            'interval_start' => '',
            'interval_end'   => '',
        ]);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record->interval_start)->toBeNull()
            ->and($record->interval_end)->toBeNull();
    });

    it('usa customer_id do schedule quando disponível', function () {
        Bus::fake();

        $company   = Company::factory()->create();
        $schedule  = rsSchedule($company);
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect($record->customer_id)->toBe($company->id);
    });

    it('salva elementos dinâmicos presentes no rawInput', function () {
        Bus::fake();

        $module = rsModule();
        $group  = ElementGroup::firstOrCreate(['name' => 'Geral']);
        $type   = ElementType::firstOrCreate(
            ['name' => 'apres_menus'],
            ['label' => 'Apresentação dos Menus', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $validated = rsValidated($agent->id, $module->id);
        $rawInput  = ['apres_menus' => '1'];

        $record = app(RecordService::class)->createRecord($schedule, $validated, $rawInput);

        expect(Element::where('record_id', $record->id)->where('element_id', $type->id)->exists())->toBeTrue();
    });

    it('não cria elementos para rawInput ausente ou vazio', function () {
        Bus::fake();

        $module = rsModule();
        $group  = ElementGroup::firstOrCreate(['name' => 'Geral']);
        ElementType::firstOrCreate(
            ['name' => 'apres_menus'],
            ['label' => 'Apresentação dos Menus', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $validated = rsValidated($agent->id, $module->id);

        $record = app(RecordService::class)->createRecord($schedule, $validated, []);

        expect(Element::where('record_id', $record->id)->count())->toBe(0);
    });

    it('despacha o job SyncRecord após criar o record', function () {
        Bus::fake();

        $schedule  = rsSchedule();
        $agent     = User::factory()->agent()->create();
        $module    = rsModule();
        $validated = rsValidated($agent->id, $module->id);

        app(RecordService::class)->createRecord($schedule, $validated, []);

        Bus::assertDispatched(SyncRecord::class);
    });

});

// ─── getPrintData() ───────────────────────────────────────────────────────────

describe('RecordService — getPrintData()', function () {

    it('retorna array com as chaves record, customer e elements', function () {
        $company  = Company::factory()->create();
        $schedule = rsSchedule($company);
        $id       = rsInsertRecord($schedule);

        $data = app(RecordService::class)->getPrintData($id);

        expect($data)->toHaveKeys(['record', 'customer', 'elements'])
            ->and($data['record']->id)->toBe($id);
    });

    it('customer vem do schedule->customer quando disponível', function () {
        $company  = Company::factory()->create();
        $schedule = rsSchedule($company);
        $id       = rsInsertRecord($schedule);

        $data = app(RecordService::class)->getPrintData($id);

        expect($data['customer']->id)->toBe($company->id);
    });

    it('elements é uma Collection agrupada pelo nome do grupo', function () {
        $company  = Company::factory()->create();
        $module   = rsModule();
        $schedule = rsSchedule($company);
        $group    = ElementGroup::firstOrCreate(['name' => 'Geral']);
        ElementType::firstOrCreate(
            ['name' => 'apres_menus'],
            ['label' => 'Apresentação dos Menus', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $id   = rsInsertRecord($schedule);
        $data = app(RecordService::class)->getPrintData($id);

        expect($data['elements'])->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($data['elements']->has('Geral'))->toBeTrue();
    });

    it('cada elemento dentro do grupo tem as chaves label, value e type', function () {
        $company  = Company::factory()->create();
        $module   = rsModule();
        $schedule = rsSchedule($company);
        $group    = ElementGroup::firstOrCreate(['name' => 'Geral']);
        $type     = ElementType::firstOrCreate(
            ['name' => 'apres_menus'],
            ['label' => 'Apresentação dos Menus', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $id   = rsInsertRecord($schedule);
        $data = app(RecordService::class)->getPrintData($id);

        $item = $data['elements']['Geral'][$type->id];

        expect($item)->toHaveKeys(['label', 'value', 'type'])
            ->and($item['label'])->toBe('Apresentação dos Menus')
            ->and($item['type'])->toBe('checkbox');
    });

    it('lança ModelNotFoundException para record inativo (status = 0)', function () {
        $company  = Company::factory()->create();
        $schedule = rsSchedule($company);

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

        expect(fn () => app(RecordService::class)->getPrintData($id))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});
