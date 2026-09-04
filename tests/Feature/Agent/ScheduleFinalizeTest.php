<?php

use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Models\User;

describe('ScheduleController — finalize', function () {
    it('agente responsável finaliza agendamento com RAT ativo', function () {
        $agent = actingAsAgent();

        $schedule = Schedule::factory()->create([
            'agent_id' => $agent->id,
            'status' => 'con',
            'requires_admin_confirmation' => false,
        ]);

        Record::factory()->active()->forSchedule($schedule)->create();

        $this->from(route('agent.schedules.show', $schedule))
            ->post(route('agent.schedules.finalize', $schedule))
            ->assertRedirect(route('agent.schedules.show', $schedule))
            ->assertSessionHas('success', 'Agendamento finalizado com sucesso.');

        $this->assertDatabaseHas('schedule', [
            'id' => $schedule->id,
            'status' => 'fin',
            'requires_admin_confirmation' => 0,
        ]);
    });

    it('não finaliza agendamento sem RAT ativo', function () {
        $agent = actingAsAgent();

        $schedule = Schedule::factory()->create([
            'agent_id' => $agent->id,
            'status' => 'con',
            'requires_admin_confirmation' => false,
        ]);

        $this->from(route('agent.schedules.show', $schedule))
            ->post(route('agent.schedules.finalize', $schedule))
            ->assertRedirect(route('agent.schedules.show', $schedule))
            ->assertSessionHas('warning', 'Registre ao menos um RAT ativo antes de finalizar o agendamento.');

        $this->assertDatabaseHas('schedule', [
            'id' => $schedule->id,
            'status' => 'con',
        ]);
    });

    it('agente não responsável recebe 403 ao tentar finalizar', function () {
        actingAsAgent();
        $owner = User::factory()->agent()->create();

        $schedule = Schedule::factory()->create([
            'agent_id' => $owner->id,
            'status' => 'con',
            'requires_admin_confirmation' => false,
        ]);

        Record::factory()->active()->forSchedule($schedule)->create();

        $this->post(route('agent.schedules.finalize', $schedule))
            ->assertForbidden();

        $this->assertDatabaseHas('schedule', [
            'id' => $schedule->id,
            'status' => 'con',
        ]);
    });
});
