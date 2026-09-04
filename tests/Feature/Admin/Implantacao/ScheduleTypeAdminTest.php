<?php

use App\Models\ScheduleType;
use Database\Seeders\ScheduleTypeSeeder;

beforeEach(function () {
    $this->seed(ScheduleTypeSeeder::class);
});

test('admin vê a lista de tipos de agendamento', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.schedule-types.index'))
        ->assertStatus(200)
        ->assertSee('Implantação')
        ->assertSee('Reunião');
});

test('não autenticado é redirecionado da lista de tipos', function () {
    $this->get(route('admin.implantacao.schedule-types.index'))->assertRedirect();
});

test('admin cria novo tipo configurável', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.schedule-types.store'), [
        'slug'              => 'visita_comercial',
        'label'             => 'Visita comercial',
        'requires_customer' => '1',
        'is_active'         => '1',
        'sort_order'        => 45,
    ])->assertRedirect(route('admin.implantacao.schedule-types.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_types', [
        'slug'              => 'visita_comercial',
        'requires_customer' => 1,
        'is_system'         => 0,
    ]);
});

test('rejeita slug duplicado', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.schedule-types.store'), [
        'slug'              => 'implantacao',
        'label'             => 'Duplicado',
        'requires_customer' => '0',
        'is_active'         => '1',
        'sort_order'        => 10,
    ])->assertSessionHasErrors('slug');
});

test('admin atualiza tipo customizado', function () {
    actingAsAdmin();
    $type = ScheduleType::factory()->create(['slug' => 'treino', 'label' => 'Treino', 'sort_order' => 100]);

    $this->put(route('admin.implantacao.schedule-types.update', $type), [
        'slug'              => 'treino',
        'label'             => 'Treinamento',
        'requires_customer' => '1',
        'is_active'         => '1',
        'sort_order'        => 99,
    ])->assertRedirect(route('admin.implantacao.schedule-types.index'));

    $this->assertDatabaseHas('schedule_types', [
        'id'                => $type->id,
        'label'             => 'Treinamento',
        'requires_customer' => 1,
    ]);
});

test('tipo de sistema não permite alterar slug nem desativar', function () {
    actingAsAdmin();
    $type = ScheduleType::where('slug', 'meeting')->firstOrFail();

    $this->put(route('admin.implantacao.schedule-types.update', $type), [
        'slug'              => 'alterado',
        'label'             => 'Reunião atualizada',
        'requires_customer' => '1',
        'is_active'         => '0',
        'sort_order'        => 40,
    ])->assertRedirect(route('admin.implantacao.schedule-types.index'));

    $type->refresh();
    expect($type->slug)->toBe('meeting');
    expect($type->is_active)->toBeTrue();
    expect($type->label)->toBe('Reunião atualizada');
    expect($type->requires_customer)->toBeTrue();
});

test('não permite excluir tipo de sistema', function () {
    actingAsAdmin();
    $type = ScheduleType::where('slug', 'meeting')->firstOrFail();

    $this->delete(route('admin.implantacao.schedule-types.destroy', $type))
        ->assertRedirect()
        ->assertSessionHas('warning');

    $this->assertDatabaseHas('schedule_types', ['id' => $type->id]);
});

test('admin remove tipo customizado sem agendamentos vinculados', function () {
    actingAsAdmin();
    $type = ScheduleType::factory()->create();

    $this->delete(route('admin.implantacao.schedule-types.destroy', $type))
        ->assertRedirect(route('admin.implantacao.schedule-types.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('schedule_types', ['id' => $type->id]);
});
