<?php

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de itens do checklist do RAT', function () {
    actingAsAdmin();
    $module = Module::factory()->create();
    ElementType::factory()->create(['module_id' => $module->id]);

    $this->get(route('admin.implantacao.rat.index'))
        ->assertStatus(200);
});

test('não autenticado é redirecionado da lista de itens', function () {
    $this->get(route('admin.implantacao.rat.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de item', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.rat.create'))
        ->assertStatus(200);
});

test('admin cria item com grupo existente (group_id como string do form HTML)', function () {
    actingAsAdmin();
    $module = Module::factory()->create();
    $group  = ElementGroup::factory()->create();

    $this->post(route('admin.implantacao.rat.store'), [
        'label'     => 'Configurar Impressora Fiscal',
        'name'      => 'configurar_impressora_fiscal',
        'module_id' => (string) $module->id,
        'group_id'  => (string) $group->id,
    ])->assertRedirect(route('admin.implantacao.rat.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_element_type', [
        'name'      => 'configurar_impressora_fiscal',
        'module_id' => $module->id,
        'group_id'  => $group->id,
    ]);
});

test('admin cria item criando um novo grupo pelo nome', function () {
    actingAsAdmin();
    $module = Module::factory()->create();

    $this->post(route('admin.implantacao.rat.store'), [
        'label'          => 'Validar NF-e',
        'name'           => 'validar_nfe',
        'module_id'      => $module->id,
        'new_group_name' => 'Fiscal',
    ])->assertRedirect(route('admin.implantacao.rat.index'));

    $this->assertDatabaseHas('schedule_record_element_group', ['name' => 'Fiscal']);
    $this->assertDatabaseHas('schedule_record_element_type', ['name' => 'validar_nfe']);
});

test('rejeita item sem módulo', function () {
    actingAsAdmin();
    $group = ElementGroup::factory()->create();

    $this->post(route('admin.implantacao.rat.store'), [
        'label'    => 'Teste',
        'name'     => 'teste_item',
        'group_id' => $group->id,
    ])->assertSessionHasErrors('module_id');
});

test('rejeita item sem grupo nem nome de novo grupo', function () {
    actingAsAdmin();
    $module = Module::factory()->create();

    $this->post(route('admin.implantacao.rat.store'), [
        'label'     => 'Teste',
        'name'      => 'teste_item_sem_grupo',
        'module_id' => $module->id,
    ])->assertSessionHasErrors('new_group_name');
});

test('rejeita item com nome técnico duplicado', function () {
    actingAsAdmin();
    $module = Module::factory()->create();
    $group  = ElementGroup::factory()->create();
    ElementType::factory()->create(['name' => 'item_existente']);

    $this->post(route('admin.implantacao.rat.store'), [
        'label'     => 'Novo',
        'name'      => 'item_existente',
        'module_id' => $module->id,
        'group_id'  => $group->id,
    ])->assertSessionHasErrors('name');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de item', function () {
    actingAsAdmin();
    $item = ElementType::factory()->create();

    $this->get(route('admin.implantacao.rat.edit', $item))
        ->assertStatus(200);
});

test('admin atualiza item com dados válidos', function () {
    actingAsAdmin();
    $item     = ElementType::factory()->create();
    $module   = Module::factory()->create();
    $group    = ElementGroup::factory()->create();

    $this->put(route('admin.implantacao.rat.update', $item), [
        'label'     => 'Rótulo Atualizado',
        'name'      => 'nome_atualizado',
        'module_id' => (string) $module->id,
        'group_id'  => (string) $group->id,
    ])->assertRedirect(route('admin.implantacao.rat.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_element_type', [
        'id'       => $item->id,
        'name'     => 'nome_atualizado',
        'group_id' => $group->id,
    ]);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove item sem registros vinculados', function () {
    actingAsAdmin();
    $item = ElementType::factory()->create();

    $this->delete(route('admin.implantacao.rat.destroy', $item))
        ->assertRedirect(route('admin.implantacao.rat.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('schedule_record_element_type', ['id' => $item->id]);
});
