<?php

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de grupos do RAT', function () {
    actingAsAdmin();
    ElementGroup::factory()->count(3)->create();

    $this->get(route('admin.implantacao.groups.index'))
        ->assertStatus(200)
        ->assertSee(ElementGroup::first()->name);
});

test('não autenticado é redirecionado da lista de grupos', function () {
    $this->get(route('admin.implantacao.groups.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de grupo', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.groups.create'))
        ->assertStatus(200);
});

test('admin cria grupo com dados válidos', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.groups.store'), [
        'name' => 'Instalação de Componentes Básicos',
    ])->assertRedirect(route('admin.implantacao.groups.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_element_group', [
        'name' => 'Instalação de Componentes Básicos',
    ]);
});

test('rejeita grupo sem nome', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.groups.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});

test('rejeita grupo com nome duplicado', function () {
    actingAsAdmin();
    ElementGroup::factory()->create(['name' => 'Grupo Existente']);

    $this->post(route('admin.implantacao.groups.store'), [
        'name' => 'Grupo Existente',
    ])->assertSessionHasErrors('name');
});

test('rejeita grupo com nome muito longo', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.groups.store'), [
        'name' => str_repeat('A', 101),
    ])->assertSessionHasErrors('name');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de grupo', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create();

    $this->get(route('admin.implantacao.groups.edit', $group))
        ->assertStatus(200)
        ->assertSee($group->name);
});

test('admin atualiza grupo com dados válidos', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create(['name' => 'Antigo']);

    $this->put(route('admin.implantacao.groups.update', $group), [
        'name' => 'Configuração de PDV',
    ])->assertRedirect(route('admin.implantacao.groups.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_element_group', ['name' => 'Configuração de PDV']);
    $this->assertDatabaseMissing('schedule_record_element_group', ['name' => 'Antigo']);
});

test('permite renomear grupo mantendo unicidade consigo mesmo', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create(['name' => 'Meu Grupo']);

    $this->put(route('admin.implantacao.groups.update', $group), [
        'name' => 'Meu Grupo',
    ])->assertRedirect(route('admin.implantacao.groups.index'));
});

test('retorna 404 ao editar grupo inexistente', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.groups.edit', 9999))
        ->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove grupo sem itens vinculados', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create();

    $this->delete(route('admin.implantacao.groups.destroy', $group))
        ->assertRedirect(route('admin.implantacao.groups.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('schedule_record_element_group', ['id' => $group->id]);
});

test('retorna warning ao tentar remover grupo com itens vinculados', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create();
    ElementType::factory()->create(['group_id' => $group->id]);

    $this->delete(route('admin.implantacao.groups.destroy', $group))
        ->assertRedirect()
        ->assertSessionHas('warning');

    $this->assertDatabaseHas('schedule_record_element_group', ['id' => $group->id]);
});
