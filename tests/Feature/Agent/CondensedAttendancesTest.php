<?php

use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ca_agent(): User
{
    return User::factory()->agent()->create();
}

function ca_ticket(): Ticket
{
    return Ticket::factory()->create();
}

function ca_attendance(int $ticketId, int $userId, array $overrides = []): Attendance
{
    return Attendance::factory()->create(array_merge([
        'ticket_id' => $ticketId,
        'user_id' => $userId,
    ], $overrides));
}

// ─── Autenticação ─────────────────────────────────────────────────────────────

describe('Condensed — autenticação', function () {

    it('visitante não autenticado é redirecionado', function () {
        $this->get(route('agent.calendar.condensed'))
            ->assertRedirect();
    });

    it('agente autenticado acessa a rota e recebe a view correta', function () {
        actingAsAgent();

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertViewIs('agent.condensed');
    });

    it('admin autenticado acessa a rota e recebe a view correta', function () {
        actingAsAdmin();

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertViewIs('agent.condensed');
    });

});

// ─── Variáveis injetadas na view ──────────────────────────────────────────────

describe('Condensed — variáveis de atendimento injetadas na view', function () {

    it('view recebe attendances como Collection', function () {
        actingAsAgent();

        $response = $this->get(route('agent.calendar.condensed'))->assertOk();

        expect($response->viewData('attendances'))
            ->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('view recebe attendances_today_count como inteiro', function () {
        actingAsAgent();

        $response = $this->get(route('agent.calendar.condensed'))->assertOk();

        expect($response->viewData('attendances_today_count'))->toBeInt();
    });

    it('expõe a nomenclatura da fila de pendências na interface condensada', function () {
        actingAsAgent();

        $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->assertSee('Visão Condensada')
            ->assertSee('Voltar ao Dashboard')
            ->assertSee('Fila de Pendências')
            ->assertSee('Com Responsável');
    });

});

// ─── Escopo por agente / admin ────────────────────────────────────────────────

describe('Condensed — escopo de atendimentos por perfil', function () {

    it('agente vê apenas seus próprios atendimentos', function () {
        $agent = actingAsAgent();
        $other = ca_agent();
        $ticket = ca_ticket();
        $now = Carbon::now();

        ca_attendance($ticket->id, $agent->id, ['created_at' => $now, 'updated_at' => $now]);
        ca_attendance($ticket->id, $other->id, ['created_at' => $now, 'updated_at' => $now]); // não deve aparecer

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances)->toHaveCount(1);
        expect($attendances->first()->user_id)->toBe($agent->id);
    });

    it('admin vê atendimentos de todos os agentes', function () {
        actingAsAdmin();
        $agent1 = ca_agent();
        $agent2 = ca_agent();
        $ticket = ca_ticket();
        $now = Carbon::now();

        ca_attendance($ticket->id, $agent1->id, ['created_at' => $now, 'updated_at' => $now]);
        ca_attendance($ticket->id, $agent2->id, ['created_at' => $now, 'updated_at' => $now]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances->count())->toBeGreaterThanOrEqual(2);
    });

});

// ─── Filtro de janela de 7 dias ───────────────────────────────────────────────

describe('Condensed — janela de 7 dias nos atendimentos', function () {

    it('exclui atendimentos com mais de 7 dias', function () {
        $agent = actingAsAgent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::now()->subDays(8)->startOfDay(),
            'updated_at' => Carbon::now()->subDays(8)->startOfDay(),
        ]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances)->toHaveCount(0);
    });

    it('inclui atendimento criado hoje', function () {
        $agent = actingAsAgent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances)->toHaveCount(1);
    });

    it('inclui atendimento criado exatamente 7 dias atrás', function () {
        $agent = actingAsAgent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::now()->subDays(7)->startOfDay(),
            'updated_at' => Carbon::now()->subDays(7)->startOfDay(),
        ]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances)->toHaveCount(1);
    });

});

// ─── Ordenação e limite ───────────────────────────────────────────────────────

describe('Condensed — ordenação e limite dos atendimentos', function () {

    it('ordena do mais antigo para o mais recente', function () {
        $agent = actingAsAgent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::now()->subHours(3),
            'updated_at' => Carbon::now()->subHours(3),
        ]);
        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::now()->subHour(),
            'updated_at' => Carbon::now()->subHour(),
        ]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances->first()->created_at->lt($attendances->last()->created_at))->toBeTrue();
    });

    it('respeita o limite máximo de 60 registros', function () {
        $agent = actingAsAdmin();
        $ticket = ca_ticket();

        Attendance::factory()->count(65)->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'created_at' => Carbon::now()->subDay(),
            'updated_at' => Carbon::now()->subDay(),
        ]);

        $attendances = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances');

        expect($attendances->count())->toBeLessThanOrEqual(60);
    });

});

// ─── KPI: attendances_today_count ────────────────────────────────────────────

describe('Condensed — attendances_today_count', function () {

    it('conta apenas atendimentos de hoje', function () {
        $agent = actingAsAgent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);
        ca_attendance($ticket->id, $agent->id, [
            'created_at' => Carbon::yesterday(),
            'updated_at' => Carbon::yesterday(),
        ]);

        $count = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances_today_count');

        expect($count)->toBe(1);
    });

    it('agente não conta atendimentos de outros agentes hoje', function () {
        $agent = actingAsAgent();
        $other = ca_agent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $other->id, [
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);

        $count = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances_today_count');

        expect($count)->toBe(0);
    });

    it('admin conta atendimentos de todos os agentes hoje', function () {
        actingAsAdmin();
        $agent1 = ca_agent();
        $agent2 = ca_agent();
        $ticket = ca_ticket();

        ca_attendance($ticket->id, $agent1->id, [
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);
        ca_attendance($ticket->id, $agent2->id, [
            'created_at' => Carbon::today(),
            'updated_at' => Carbon::today(),
        ]);

        $count = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances_today_count');

        expect($count)->toBeGreaterThanOrEqual(2);
    });

    it('retorna zero quando não há atendimentos hoje', function () {
        actingAsAgent();

        $count = $this->get(route('agent.calendar.condensed'))
            ->assertOk()
            ->viewData('attendances_today_count');

        expect($count)->toBe(0);
    });

});
