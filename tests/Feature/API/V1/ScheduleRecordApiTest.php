<?php

/**
 * Regressão: os métodos da API de Records/Schedules tiveram os corpos
 * removidos na migração da documentação OpenAPI (retornavam null contra
 * return types declarados → 500). Estes testes garantem que os endpoints
 * consumidos pelo frontend Blade respondem corretamente.
 */

use App\Models\Schedule;
use App\Models\Schedule\Record;

// ─── Testes ──────────────────────────────────────────────────────────────────

describe('API V1 — Schedule Records', function () {

    it('usuário não autenticado recebe 401 ao buscar um record', function () {
        $record = Record::factory()->create();

        $this->getJson("/api/v1/schedules/{$record->schedule_id}/records/{$record->id}")
            ->assertUnauthorized();
    });

    it('retorna o record com elements para o formulário de edição do RAT', function () {
        actingAsAdmin();
        $record = Record::factory()->create();

        $this->getJson("/api/v1/schedules/{$record->schedule_id}/records/{$record->id}")
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'start', 'end', 'elements']]);
    });

    it('lista records por período', function () {
        actingAsAdmin();
        $record = Record::factory()->create(['start' => now(), 'end' => now()->addHour()]);

        $this->getJson('/api/v1/schedules/' . $record->schedule_id . '/records?' . http_build_query([
            'start' => now()->subDay()->toDateString(),
            'end'   => now()->addDay()->toDateString(),
        ]))
            ->assertOk()
            ->assertJsonStructure(['data']);
    });

});

describe('API V1 — Schedules', function () {

    it('retorna o calendário semanal de agendamentos', function () {
        actingAsAdmin();
        Schedule::factory()->create(['start_at' => now()->startOfWeek(Carbon\Carbon::MONDAY)->addHours(9)]);

        $this->getJson('/api/v1/schedules?start=' . now()->toDateString())
            ->assertOk();
    });

    it('recusa finalizar agendamento sem atividades', function () {
        actingAsAdmin();
        $schedule = Schedule::factory()->create(['status' => 'con']);

        $this->postJson("/api/v1/schedules/{$schedule->id}/finalize")
            ->assertStatus(422)
            ->assertJson(['status' => false]);
    });

});
