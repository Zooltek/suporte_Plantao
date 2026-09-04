<?php

use App\Models\Customer;
use App\Models\Schedule;
use Illuminate\Support\Facades\DB;

test('agente pode criar agendamento interno sem cliente nem modulo', function () {
    $agent = actingAsAgent();

    $response = $this->post(route('agent.schedules.store'), [
        'kind' => Schedule::KIND_MEDICAL,
        'title' => 'Consulta medica',
        'date' => now()->format('Y-m-d'),
        'start_hour' => '09:30',
        'obs' => 'Retorno clinico',
    ]);

    $response
        ->assertRedirect(route('agent.calendar.condensed', ['active' => 'schedules']))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('schedule', [
        'agent_id' => $agent->id,
        'kind' => Schedule::KIND_MEDICAL,
        'title' => 'Consulta medica',
        'customer_id' => null,
        'module_id' => null,
        'status' => 'pen',
        'requires_admin_confirmation' => 0,
    ]);
});

test('somente admin confirma agendamento pendente vindo de ticket', function () {
    $schedule = Schedule::factory()->create([
        'kind' => Schedule::KIND_TICKET,
        'title' => 'Visita tecnica - Ticket #10',
        'ticket_id' => 10,
        'status' => 'sch',
        'requires_admin_confirmation' => true,
    ]);

    actingAsAgent();

    $this->from(route('agent.calendar.condensed', ['active' => 'schedules']))
        ->post(route('agent.schedules.confirm', $schedule))
        ->assertForbidden();

    actingAsAdmin();

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

test('formulario de agendamento via ticket exibe contexto correto do ticket', function () {
    actingAsAgent();

    $customer = Customer::factory()->create([
        'trade_name' => 'Rede Exemplo',
    ]);

    $ticketId = DB::table('ticketit')->insertGetId([
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
        'company_id' => $customer->id,
        'origin_id' => 1,
        'contact' => 'Marina',
        'visible' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->get(route('agent.schedules.create', ['ticket_id' => $ticketId]))
        ->assertOk()
        ->assertSee("Ticket #{$ticketId} - aguardando admin", false)
        ->assertSee('Rede Exemplo')
        ->assertSee('Marina')
        ->assertSee("Visita tecnica - Ticket #{$ticketId}");
});

test('calendario mostra confirmacao apenas para admin em agendamento pendente', function () {
    $agent = actingAsAgent();

    $pendingSchedule = Schedule::factory()->create([
        'kind' => Schedule::KIND_TICKET,
        'title' => 'Visita tecnica - Ticket #22',
        'ticket_id' => 22,
        'status' => 'sch',
        'requires_admin_confirmation' => true,
        'start_at' => now()->startOfWeek()->addDay()->setTime(10, 0),
        'agent_id' => $agent->id,
    ]);

    actingAsAdmin();

    $this->get(route('agent.calendar.condensed', ['active' => 'schedules']))
        ->assertOk()
        ->assertSee('Aguardando admin')
        ->assertSee('Confirmar');

    test()->actingAs($agent, 'admin');

    $this->get(route('agent.calendar.condensed', ['active' => 'schedules']))
        ->assertOk()
        ->assertSee('Aguardando admin')
        ->assertDontSee('Confirmar');
});

test('calendario de agendamentos reforça o contexto de implantacao', function () {
    actingAsAgent();

    $this->get(route('agent.calendar.condensed', ['active' => 'schedules']))
        ->assertOk()
        ->assertSee('Calendário de Implantação')
        ->assertSee('Voltar para Implantação')
        ->assertDontSee('Voltar ao Dashboard');
});
