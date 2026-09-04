<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Services\Agent\RecordService;
use Illuminate\Support\Facades\DB;

// ─────────────────────────────────────────────
// Helpers locais
// ─────────────────────────────────────────────

/** Cria agente sem autenticar na sessão */
function rcAgent(): User
{
    return User::factory()->agent()->create();
}

/** Cria um Module */
function rcModule(string $name = 'Implantação', ?string $project = 'EasyMaster'): Module
{
    return Module::firstOrCreate(['name' => $name], ['project' => $project]);
}

/** Cria Schedule com um agent criado na factory, sem autenticar */
function rcSchedule(?Company $company = null, string $status = 'con'): Schedule
{
    $company ??= Company::factory()->create();
    $agent     = rcAgent();
    $module    = rcModule();

    return Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => $status,
    ]);
}

/** Insere record ativo via DB (schedule_id e contact são NOT NULL) */
function rcRecord(Schedule $schedule): Record
{
    $id = DB::table('schedule_record')->insertGetId([
        'schedule_id' => $schedule->id,
        'module_id'   => $schedule->module_id,
        'customer_id' => $schedule->customer_id,
        'agent_id'    => $schedule->agent_id,
        'status'      => 1,
        'contact'     => 'Contato Teste',
        'start'       => now()->subHours(3),
        'end'         => now()->subHours(1),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    return Record::find($id);
}

// ─────────────────────────────────────────────
// Bug 6 — resolveModules usa Company, não Customer
// ─────────────────────────────────────────────
describe('RecordController — resolveModules usa Company::find (Bug 6)', function () {

    it('create() retorna 200 sem TypeError quando schedule tem customer_id definido', function () {
        // Antes do fix: $schedule->customer retornava Customer → TypeError no service
        // Após o fix:  Company::find($schedule->customer_id) → Company → sem erro
        actingAsAgent();
        $company  = Company::factory()->create();
        $schedule = rcSchedule($company);

        $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.create');
    });

    it('módulos configurados para o cliente aparecem via Company quando customer_id está definido', function () {
        actingAsAgent();
        $company = Company::factory()->create();
        $module  = rcModule('Módulo do Cliente', 'EasyMaster');
        $company->scheduleModules()->attach($module->id);
        $schedule = rcSchedule($company);

        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->viewData('modules');

        expect($modules->flatten()->pluck('name')->toArray())->toContain('Módulo do Cliente');
    });

    it('fallback para todos os módulos quando schedule não tem customer', function () {
        actingAsAgent();
        Module::create(['name' => 'Módulo Fallback', 'project' => 'EasyMaster']);

        // Schedule sem customer_id
        $schedule = Schedule::factory()->create([
            'customer_id' => null,
            'agent_id'    => rcAgent()->id,
            'module_id'   => rcModule()->id,
            'status'      => 'con',
        ]);

        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->viewData('modules');

        expect($modules->flatten()->pluck('name')->toArray())->toContain('Módulo Fallback');
    });

});

// ─────────────────────────────────────────────
// index()
// ─────────────────────────────────────────────
describe('RecordController — index()', function () {

    it('retorna view com records ativos do schedule', function () {
        actingAsAgent();
        $schedule = rcSchedule();
        rcRecord($schedule);

        $this->get(route('agent.record.index', ['schedule' => $schedule->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.list');
    });

    it('redireciona visitante não autenticado para login', function () {
        $schedule = rcSchedule();

        // Usuário NÃO autenticado (actingAsAgent não chamado)
        $this->get(route('agent.record.index', ['schedule' => $schedule->id]))
            ->assertRedirect();
    });

});

// ─────────────────────────────────────────────
// create()
// ─────────────────────────────────────────────
describe('RecordController — create()', function () {

    it('exibe formulário de criação de RAT para agente autenticado', function () {
        actingAsAgent();
        $schedule = rcSchedule();

        $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.create');
    });

    it('envia modules agrupados por project para a view', function () {
        actingAsAgent();

        Module::create(['name' => 'Módulo A', 'project' => 'EasyMaster']);
        Module::create(['name' => 'Módulo B', 'project' => 'EasyControl']);
        Module::create(['name' => 'Módulo C', 'project' => null]);

        $schedule = rcSchedule();

        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->viewData('modules');

        expect($modules)->toBeInstanceOf(\Illuminate\Support\Collection::class)
            ->and($modules->keys()->toArray())->toContain('EasyMaster', 'EasyControl', 'Geral');
    });

    it('módulos sem project aparecem no grupo Geral', function () {
        actingAsAgent();
        Module::create(['name' => 'Genérico', 'project' => null]);
        $schedule = rcSchedule();

        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->viewData('modules');

        expect($modules->has('Geral'))->toBeTrue();
    });

    it('redireciona visitante não autenticado', function () {
        $scheduleId = rcSchedule()->id;

        // Sem actingAsAgent() — usuário não autenticado
        $this->get(route('agent.record.create', ['schedule' => $scheduleId]))
            ->assertRedirect();
    });

});

// ─────────────────────────────────────────────
// edit()
// ─────────────────────────────────────────────
describe('RecordController — edit()', function () {

    it('exibe formulário de edição com dados do record', function () {
        actingAsAgent();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);

        $this->get(route('agent.record.edit', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.create')
            ->assertViewHas('record', fn ($r) => $r->id === $record->id);
    });

    it('retorna 404 para record inativo (status = 0)', function () {
        actingAsAgent();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);
        DB::table('schedule_record')->where('id', $record->id)->update(['status' => 0]);

        $this->get(route('agent.record.edit', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertNotFound();
    });

});

// ─────────────────────────────────────────────
// store()
// ─────────────────────────────────────────────
describe('RecordController — store()', function () {

    it('agente cria record e recebe tela de confirmação', function () {
        $agent    = actingAsAgent();
        $module   = Module::create(['name' => 'Estoque RC', 'project' => 'EasyMaster']);
        $schedule = rcSchedule(status: 'con');

        $this->mock(RecordService::class, function ($mock) use ($schedule, $module, $agent) {
            $mock->shouldReceive('createRecord')
                ->once()
                ->andReturn(new Record([
                    'schedule_id' => $schedule->id,
                    'module_id'   => $module->id,
                    'agent_id'    => $agent->id,
                    'status'      => 1,
                    'start'       => now()->subHours(2),
                    'end'         => now()->subHour(),
                ]));
        });

        $this->post(route('agent.record.store', ['schedule' => $schedule->id]), [
            'date'       => now()->format('Y-m-d'),
            'start_hour' => '09:00',
            'end_hour'   => '11:00',
            'agent_id'   => $agent->id,
            'module_id'  => $module->id,
            'contact'    => 'João',
        ])->assertRedirect(route('agent.schedules.show', $schedule->id))
          ->assertSessionHas('success', 'RAT registrado com sucesso.');
    });

    it('falha de validação quando date está ausente', function () {
        $agent    = actingAsAgent();
        $module   = Module::create(['name' => 'Estoque RC2', 'project' => 'EasyMaster']);
        $schedule = rcSchedule(status: 'con');

        $this->post(route('agent.record.store', ['schedule' => $schedule->id]), [
            // 'date' ausente
            'start_hour' => '09:00',
            'end_hour'   => '11:00',
            'agent_id'   => $agent->id,
            'module_id'  => $module->id,
        ])->assertSessionHasErrors('date');
    });

    it('retorna 403 quando agendamento já está finalizado', function () {
        $agent    = actingAsAgent();
        $module   = Module::create(['name' => 'Estoque RC3', 'project' => 'EasyMaster']);
        $schedule = rcSchedule(status: 'fin'); // isFinalized() = true

        $this->post(route('agent.record.store', ['schedule' => $schedule->id]), [
            'date'       => now()->format('Y-m-d'),
            'start_hour' => '09:00',
            'end_hour'   => '11:00',
            'agent_id'   => $agent->id,
            'module_id'  => $module->id,
            'contact'    => 'João',
        ])->assertForbidden();
    });

});

// ─────────────────────────────────────────────
// destroy()
// ─────────────────────────────────────────────
describe('RecordController — destroy()', function () {

    it('admin soft-deleta um record (status → 0)', function () {
        actingAsAdmin();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);

        $this->delete(route('agent.record.destroy', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertRedirect(route('agent.record.index', ['schedule' => $schedule->id]));

        $this->assertDatabaseHas('schedule_record', ['id' => $record->id, 'status' => 0]);
    });

    it('agente sem permissão de admin recebe 401', function () {
        actingAsAgent();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);

        $this->delete(route('agent.record.destroy', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertForbidden();
    });

    it('retorna 404 para record já inativo', function () {
        actingAsAdmin();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);
        DB::table('schedule_record')->where('id', $record->id)->update(['status' => 0]);

        $this->delete(route('agent.record.destroy', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertNotFound();
    });

});

// ─────────────────────────────────────────────
// print()
// ─────────────────────────────────────────────
describe('RecordController — print()', function () {

    it('exibe view de impressão do record via RecordService', function () {
        actingAsAgent();
        $schedule = rcSchedule();
        $record   = rcRecord($schedule);

        $this->mock(RecordService::class, function ($mock) use ($record, $schedule) {
            $mock->shouldReceive('getPrintData')
                ->once()
                ->with($record->id)
                ->andReturn([
                    'record'   => $record,
                    'customer' => $schedule->customer,
                    'elements' => collect([]),
                ]);
        });

        $this->get(route('agent.record.print', ['schedule' => $schedule->id, 'record' => $record->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.print')
            ->assertSee('img/amura-logo-light.png', false)
            ->assertDontSee('marca-old.png', false)
            ->assertDontSee('Logo Consuldata', false);
    });

});
