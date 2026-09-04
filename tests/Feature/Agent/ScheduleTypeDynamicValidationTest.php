<?php

use App\Models\Customer;
use App\Models\ScheduleType;
use Database\Seeders\ScheduleTypeSeeder;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::forget('schedule_types.active');
    $this->seed(ScheduleTypeSeeder::class);
});

afterEach(function () {
    Cache::forget('schedule_types.active');
});

test('tipo reunião (requires_customer=false) aceita agendamento sem cliente', function () {
    actingAsAgent();

    $this->post(route('agent.schedules.store'), [
        'kind' => 'meeting',
        'title' => 'Reunião semanal do time',
        'date' => now()->format('Y-m-d'),
        'start_hour' => '14:00',
    ])->assertRedirect()->assertSessionHas('success');

    $typeId = ScheduleType::where('slug', 'meeting')->value('id');

    $this->assertDatabaseHas('schedule', [
        'kind' => 'meeting',
        'schedule_type_id' => $typeId,
        'customer_id' => null,
    ]);
});

test('tipo implantação (requires_customer=true) exige cliente', function () {
    actingAsAgent();

    $this->post(route('agent.schedules.store'), [
        'kind' => 'implantacao',
        'title' => 'Kickoff sem cliente',
        'date' => now()->format('Y-m-d'),
        'start_hour' => '10:00',
    ])->assertSessionHasErrors('customer_id');
});

test('tipo implantação aceita com cliente vinculado', function () {
    actingAsAgent();
    $customer = Customer::factory()->create();

    $this->post(route('agent.schedules.store'), [
        'kind' => 'implantacao',
        'title' => 'Kickoff de implantação',
        'customer_id' => $customer->id,
        'date' => now()->format('Y-m-d'),
        'start_hour' => '10:00',
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('schedule', [
        'kind' => 'implantacao',
        'customer_id' => $customer->id,
    ]);
});

test('tipo customizado criado pelo admin passa a aceitar agendamento', function () {
    actingAsAgent();

    ScheduleType::factory()->create([
        'slug' => 'visita_comercial',
        'label' => 'Visita comercial',
        'requires_customer' => false,
        'is_active' => true,
    ]);
    Cache::forget('schedule_types.active');

    $this->post(route('agent.schedules.store'), [
        'kind' => 'visita_comercial',
        'title' => 'Prospecção',
        'date' => now()->format('Y-m-d'),
        'start_hour' => '11:30',
    ])->assertRedirect()->assertSessionHas('success');

    $this->assertDatabaseHas('schedule', [
        'kind' => 'visita_comercial',
        'customer_id' => null,
    ]);
});

test('tipo inativo é rejeitado pela validação', function () {
    actingAsAgent();

    ScheduleType::factory()->inactive()->create(['slug' => 'desativado']);
    Cache::forget('schedule_types.active');

    $this->post(route('agent.schedules.store'), [
        'kind' => 'desativado',
        'title' => 'Teste',
        'date' => now()->format('Y-m-d'),
        'start_hour' => '09:00',
    ])->assertSessionHasErrors('kind');
});
