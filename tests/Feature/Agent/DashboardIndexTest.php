<?php

use App\Models\Customer;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Support\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function di_agent(): User
{
    return User::factory()->agent()->create();
}

function di_schedule(int $agentId, string $status, Carbon $startAt): Schedule
{
    $customer = Customer::factory()->create();

    return Schedule::factory()->create([
        'agent_id'   => $agentId,
        'customer_id' => $customer->id,
        'status'     => $status,
        'start_at'   => $startAt,
    ]);
}

// ─── Autenticação ─────────────────────────────────────────────────────────────

describe('Dashboard index — autenticação', function () {

    it('visitante não autenticado é redirecionado', function () {
        $this->get(route('agent.index'))
            ->assertRedirect();
    });

    it('agente autenticado acessa o dashboard e recebe a view correta', function () {
        actingAsAgent();

        $this->get(route('agent.index'))
            ->assertOk()
            ->assertViewIs('agent.index');
    });

    it('admin autenticado acessa o dashboard e recebe a view correta', function () {
        actingAsAdmin();

        $this->get(route('agent.index'))
            ->assertOk()
            ->assertViewIs('agent.index');
    });

});

// ─── Variáveis injetadas pela getIndexData ────────────────────────────────────

describe('Dashboard index — variáveis da view', function () {

    it('view recebe schedules_today como inteiro', function () {
        actingAsAgent();

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_today'))->toBeInt();
    });

    it('view recebe schedules_overdue como inteiro', function () {
        actingAsAgent();

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_overdue'))->toBeInt();
    });

    it('view recebe schedules_upcoming como inteiro', function () {
        actingAsAgent();

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_upcoming'))->toBeInt();
    });

    it('dashboard separa claramente atendimento e implantação no acesso rápido', function () {
        actingAsAgent();

        $this->get(route('agent.index'))
            ->assertOk()
            ->assertSee('Painel do Agente')
            ->assertSee('Atendimento')
            ->assertSee('Implantação')
            ->assertSee('Calendário de Implantação');
    });

});

// ─── countSchedules: escopo por tipo ─────────────────────────────────────────

describe('Dashboard index — countSchedules por tipo', function () {

    it('conta agendamentos de hoje (today)', function () {
        $agent = actingAsAgent();

        di_schedule($agent->id, 'sch', Carbon::today()->setHour(10));
        di_schedule($agent->id, 'sch', Carbon::yesterday());  // não conta

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_today'))->toBe(1);
    });

    it('conta agendamentos atrasados (overdue)', function () {
        $agent = actingAsAgent();

        di_schedule($agent->id, 'con', Carbon::yesterday());
        di_schedule($agent->id, 'sch', Carbon::today()->setHour(10)); // não conta

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_overdue'))->toBe(1);
    });

    it('conta agendamentos futuros — até 7 dias (upcoming)', function () {
        $agent = actingAsAgent();

        di_schedule($agent->id, 'sch', Carbon::tomorrow()->setHour(9));
        di_schedule($agent->id, 'sch', Carbon::today()->setHour(10)); // hoje — não conta como upcoming

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_upcoming'))->toBe(1);
    });

    it('não conta status finalizados (fin/can/del)', function () {
        $agent = actingAsAgent();

        di_schedule($agent->id, 'fin', Carbon::today()->setHour(10));
        di_schedule($agent->id, 'can', Carbon::today()->setHour(10));

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_today'))->toBe(0);
    });

});

// ─── countSchedules: escopo por perfil ───────────────────────────────────────

describe('Dashboard index — countSchedules escopo agente vs admin', function () {

    it('agente vê apenas seus próprios agendamentos', function () {
        $agent = actingAsAgent();
        $other = di_agent();

        di_schedule($agent->id, 'sch', Carbon::today()->setHour(10));
        di_schedule($other->id, 'sch', Carbon::today()->setHour(11)); // de outro agente

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_today'))->toBe(1);
    });

    it('admin vê agendamentos de todos os agentes', function () {
        actingAsAdmin();
        $agent1 = di_agent();
        $agent2 = di_agent();

        di_schedule($agent1->id, 'sch', Carbon::today()->setHour(10));
        di_schedule($agent2->id, 'sch', Carbon::today()->setHour(11));

        $response = $this->get(route('agent.index'))->assertOk();

        expect($response->viewData('schedules_today'))->toBeGreaterThanOrEqual(2);
    });

});
