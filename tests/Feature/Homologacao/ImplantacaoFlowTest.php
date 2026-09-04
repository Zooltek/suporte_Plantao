<?php

use App\Models\Company;
use App\Models\Schedule;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\User;

// ── Helpers ────────────────────────────────────────────────────────────────────

function implSchedule(string $status = 'sch', bool $requiresConfirmation = false): Schedule
{
    $company = Company::factory()->create();
    $agent   = User::factory()->agent()->create();
    $module  = Module::firstOrCreate(['name' => 'Implantação'], ['project' => 'EasyMaster']);

    return Schedule::factory()->create([
        'customer_id'                 => $company->id,
        'agent_id'                    => $agent->id,
        'module_id'                   => $module->id,
        'status'                      => $status,
        'requires_admin_confirmation' => $requiresConfirmation,
    ]);
}

// ── Fluxo 7 — Implantação de Cliente ─────────────────────────────────────────

describe('Fluxo 7 — Implantação no fluxo web atual', function () {

    it('agente acessa a visão geral de implantação com os atalhos atuais', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.index'))
            ->assertOk()
            ->assertViewIs('agent.implantacao.index')
            ->assertSee('Implantação')
            ->assertSee('Novo Agendamento')
            ->assertSee('Agendamentos de Implantação');
    });

    it('agente acessa a listagem de agendamentos de implantação', function () {
        actingAsAgent();

        $this->get(route('agent.implantacao.schedules'))
            ->assertOk()
            ->assertViewIs('agent.implantacao.schedules')
            ->assertSee('Agendamentos de Implantação')
            ->assertSee('Visão Geral')
            ->assertSee('Calendário de Implantação');
    });

    it('admin confirma agendamento pendente de implantação pelo calendário condensado', function () {
        $schedule = implSchedule('sch', requiresConfirmation: true);
        $schedule->forceFill([
            'ticket_id' => 44,
            'title' => 'Visita tecnica - Ticket #44',
            'start_at' => now()->startOfWeek()->addDay()->setTime(14, 0),
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

    it('agendamento de implantação confirmado permite abrir o formulário de RAT', function () {
        actingAsAgent();
        $schedule = implSchedule('con');

        $this->get(route('agent.record.create', $schedule))
            ->assertOk()
            ->assertViewIs('agent.schedule.record.create')
            ->assertSee('Registrar Atendimento');
    });

    it('detalhe do agendamento de implantação exibe o bloco de RATs', function () {
        actingAsAgent();
        $schedule = implSchedule('con');
        $schedule->load(['agent', 'customer', 'module']);

        $this->get(route('agent.schedules.show', $schedule))
            ->assertOk()
            ->assertViewIs('agent.schedule.show')
            ->assertViewHas('schedule', fn ($s) => $s->id === $schedule->id)
            ->assertSee('Registros de atendimento (RAT)')
            ->assertSee('Novo RAT');
    });

    it('usuário finaliza o agendamento de implantação pela interface web', function () {
        $agent = actingAsAgent();
        $schedule = implSchedule('con');
        $schedule->forceFill([
            'agent_id' => $agent->id,
            'title' => 'Implantação Cliente Fluxo Web',
        ])->save();

        Record::factory()->active()->forSchedule($schedule)->create();

        $this->get(route('agent.schedules.show', $schedule))
            ->assertOk()
            ->assertSee('Finalizar')
            ->assertSee('Novo RAT');

        $this->from(route('agent.schedules.show', $schedule))
            ->post(route('agent.schedules.finalize', $schedule))
            ->assertRedirect(route('agent.schedules.show', $schedule))
            ->assertSessionHas('success', 'Agendamento finalizado com sucesso.');

        $this->assertDatabaseHas('schedule', [
            'id' => $schedule->id,
            'status' => 'fin',
        ]);

        $this->get(route('agent.implantacao.schedules'))
            ->assertOk()
            ->assertDontSee('Implantação Cliente Fluxo Web');
    });

});
