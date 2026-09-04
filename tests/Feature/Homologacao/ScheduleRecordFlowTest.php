<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;
use App\Services\Agent\RecordService;
use Illuminate\Support\Facades\DB;

// ── Helpers ────────────────────────────────────────────────────────────────────

function homologSchedule(?Company $company = null, string $status = 'con'): Schedule
{
    $company = $company ?? Company::factory()->create();
    $agent   = User::factory()->agent()->create();
    $module  = Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);

    return Schedule::factory()->create([
        'customer_id' => $company->id,
        'agent_id'    => $agent->id,
        'module_id'   => $module->id,
        'status'      => $status,
    ]);
}

function homologRecord(Schedule $schedule): Record
{
    $id = DB::table('schedule_record')->insertGetId([
        'schedule_id' => $schedule->id,
        'module_id'   => $schedule->module_id,
        'customer_id' => $schedule->customer_id,
        'agent_id'    => $schedule->agent_id,
        'status'      => 1,
        'contact'     => 'Contato Homologação',
        'start'       => now()->subHours(3),
        'end'         => now()->subHours(1),
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    return Record::find($id);
}

function homologTicketId(Company $company): int
{
    return DB::table('ticketit')->insertGetId([
        'subject' => 'Visita na loja matriz',
        'content' => 'Cliente solicitou visita tecnica.',
        'status_id' => 1,
        'priority_id' => 1,
        'user_id' => 1,
        'agent_id' => null,
        'category_id' => 1,
        'sub_category_id' => null,
        'rate_id' => 0,
        'files' => null,
        'conversation_id_list' => null,
        'company_id' => $company->id,
        'origin_id' => 1,
        'contact' => 'Marina',
        'visible' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
}

// ── Fluxo 4 — Agendamento e RAT no fluxo web atual ───────────────────────────

describe('Fluxo 4 — Agendamento e RAT no fluxo web atual', function () {

    it('tela de novo agendamento pela agenda exibe o formulário atual', function () {
        actingAsAgent();

        $this->get(route('agent.schedules.create'))
            ->assertOk()
            ->assertSee('Novo Agendamento')
            ->assertSee('Tipo de agendamento')
            ->assertSee('Cliente');
    });

    it('agendamento iniciado por ticket exibe o contexto atual do chamado', function () {
        actingAsAgent();
        $company = Company::factory()->create(['trade_name' => 'Rede Exemplo']);
        $ticketId = homologTicketId($company);

        $this->get(route('agent.schedules.create', ['ticket_id' => $ticketId]))
            ->assertOk()
            ->assertSee("Ticket #{$ticketId} - aguardando admin", false)
            ->assertSee('Rede Exemplo')
            ->assertSee('Marina')
            ->assertSee("Visita tecnica - Ticket #{$ticketId}");
    });

    it('admin confirma agendamento pendente pelo calendário condensado', function () {
        $schedule = homologSchedule(status: 'sch');
        $schedule->forceFill([
            'ticket_id' => 10,
            'title' => 'Visita tecnica - Ticket #10',
            'requires_admin_confirmation' => true,
            'start_at' => now()->startOfWeek()->addDay()->setTime(10, 0),
        ])->save();

        actingAsAdmin();

        $this->get(route('agent.calendar.condensed', ['active' => 'schedules']))
            ->assertOk()
            ->assertSee('Aguardando admin')
            ->assertSee('Confirmar');

        $this->from(route('agent.calendar.condensed', ['active' => 'schedules']))
            ->post(route('agent.schedules.confirm', $schedule))
            ->assertRedirect(route('agent.calendar.condensed', ['active' => 'schedules']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedule', [
            'id' => $schedule->id,
            'status' => 'con',
            'requires_admin_confirmation' => 0,
        ]);
    });

    it('formulário de RAT abre sem erro quando o agendamento possui cliente', function () {
        actingAsAgent();
        $company  = Company::factory()->create();
        $schedule = homologSchedule($company);

        $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.create')
            ->assertSee('Registrar Atendimento');
    });

    it('módulos do cliente aparecem corretamente no formulário de RAT', function () {
        // Arrange
        actingAsAgent();
        $company = Company::factory()->create();
        $module  = Module::firstOrCreate(['name' => 'Módulo Cliente RAT'], ['project' => 'EasyMaster']);
        $company->scheduleModules()->attach($module->id);
        $schedule = homologSchedule($company);

        // Act
        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
                        ->assertOk()
                        ->viewData('modules');

        // Assert — módulo do cliente presente no payload
        expect($modules->flatten()->pluck('name')->toArray())->toContain('Módulo Cliente RAT');
    });

    it('fallback exibe todos os módulos quando o agendamento nao tem cliente', function () {
        // Arrange
        actingAsAgent();
        Module::create(['name' => 'Módulo Fallback RAT', 'project' => 'EasyMaster']);

        $schedule = Schedule::factory()->create([
            'customer_id' => null,
            'agent_id'    => User::factory()->agent()->create()->id,
            'module_id'   => Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster'])->id,
            'status'      => 'con',
        ]);

        // Act
        $modules = $this->get(route('agent.record.create', ['schedule' => $schedule->id]))
                        ->assertOk()
                        ->viewData('modules');

        // Assert — fallback exibe todos os módulos
        expect($modules->flatten()->pluck('name')->toArray())->toContain('Módulo Fallback RAT');
    });

    it('salvamento do RAT redireciona para o detalhe do agendamento com feedback de sucesso', function () {
        // Arrange
        $agent    = actingAsAgent();
        $module   = Module::create(['name' => 'Módulo Store RAT', 'project' => 'EasyMaster']);
        $schedule = homologSchedule(status: 'con');

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

        // Act
        $this->post(route('agent.record.store', ['schedule' => $schedule->id]), [
            'date'       => now()->format('Y-m-d'),
            'start_hour' => '09:00',
            'end_hour'   => '11:00',
            'agent_id'   => $agent->id,
            'module_id'  => $module->id,
            'contact'    => 'João Homolog',
        ])->assertRedirect(route('agent.schedules.show', $schedule->id))
          ->assertSessionHas('success', 'RAT registrado com sucesso.');
    });

    it('listagem de RATs ativos do agendamento retorna 200', function () {
        actingAsAgent();
        $schedule = homologSchedule();
        homologRecord($schedule);

        $this->get(route('agent.record.index', ['schedule' => $schedule->id]))
             ->assertOk()
             ->assertViewIs('agent.schedule.record.list');
    });

    it('agendamento finalizado retorna 403 ao tentar adicionar novo RAT', function () {
        // Arrange
        $agent    = actingAsAgent();
        $module   = Module::create(['name' => 'Módulo Fin RAT', 'project' => 'EasyMaster']);
        $schedule = homologSchedule(status: 'fin'); // isFinalized() = true

        // Act & Assert
        $this->post(route('agent.record.store', ['schedule' => $schedule->id]), [
            'date'       => now()->format('Y-m-d'),
            'start_hour' => '09:00',
            'end_hour'   => '11:00',
            'agent_id'   => $agent->id,
            'module_id'  => $module->id,
            'contact'    => 'João',
        ])->assertForbidden();
    });

    it('admin pode soft deletar um RAT', function () {
        actingAsAdmin();
        $schedule = homologSchedule();
        $record   = homologRecord($schedule);

        $this->delete(route('agent.record.destroy', [
            'schedule' => $schedule->id,
            'record'   => $record->id,
        ]))->assertRedirect(route('agent.record.index', ['schedule' => $schedule->id]));

        $this->assertDatabaseHas('schedule_record', ['id' => $record->id, 'status' => 0]);
    });

});
