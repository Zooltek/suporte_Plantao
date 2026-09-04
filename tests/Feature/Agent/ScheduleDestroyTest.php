<?php

use App\Models\Schedule;
use App\Models\Schedule\Record;

describe('ScheduleController — destroy', function () {

    it('admin pode excluir agendamento agendado (status sch)', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'sch', 'requires_admin_confirmation' => false]);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect(route('agent.calendar.condensed', ['active' => 'schedules']))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'status' => 'del']);
    });

    it('admin pode excluir agendamento confirmado (status con)', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'con', 'requires_admin_confirmation' => false]);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect(route('agent.calendar.condensed', ['active' => 'schedules']));

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'status' => 'del']);
    });

    it('agente não admin recebe 403 ao tentar excluir', function () {
        actingAsAgent();
        $schedule = Schedule::factory()->create(['status' => 'sch', 'requires_admin_confirmation' => false]);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertForbidden();
    });

    it('não permite excluir agendamento finalizado', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'fin']);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'status' => 'fin']);
    });

    it('não permite excluir agendamento cancelado', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'can']);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'status' => 'can']);
    });

    it('não permite excluir agendamento com RATs vinculadas', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'sch', 'requires_admin_confirmation' => false]);
        Record::factory()->create(['schedule_id' => $schedule->id, 'status' => 1]);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect()
            ->assertSessionHas('warning');

        $this->assertDatabaseHas('schedule', ['id' => $schedule->id, 'status' => 'sch']);
    });

    it('requisição não autenticada redireciona para login', function () {
        $schedule = Schedule::factory()->create(['status' => 'sch']);

        $this->delete(route('agent.schedules.destroy', $schedule->id))
            ->assertRedirect();
    });

});
