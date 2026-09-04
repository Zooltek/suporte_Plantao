<?php

use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de módulos do RAT', function () {
    actingAsAdmin();
    Module::factory()->count(3)->create();

    $this->get(route('admin.implantacao.rat-modules.index'))
        ->assertStatus(200)
        ->assertSee(Module::first()->name);
});

test('não autenticado é redirecionado da lista de módulos', function () {
    $this->get(route('admin.implantacao.rat-modules.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de módulo', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.rat-modules.create'))
        ->assertStatus(200);
});

test('admin cria módulo com dados válidos', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.rat-modules.store'), [
        'name'        => 'Instalação',
        'project'     => 'EasyMaster',
        'description' => 'Módulo de instalação do EasyMaster',
    ])->assertRedirect(route('admin.implantacao.rat-modules.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_module', [
        'name'    => 'Instalação',
        'project' => 'EasyMaster',
    ]);
});

test('admin cria módulo sem projeto (campo opcional)', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.rat-modules.store'), [
        'name' => 'Treinamento',
    ])->assertRedirect(route('admin.implantacao.rat-modules.index'));

    $this->assertDatabaseHas('schedule_record_module', ['name' => 'Treinamento']);
});

test('rejeita módulo sem nome', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.rat-modules.store'), [
        'name' => '',
    ])->assertSessionHasErrors('name');
});

test('rejeita módulo com nome duplicado', function () {
    actingAsAdmin();
    Module::factory()->create(['name' => 'Vendas']);

    $this->post(route('admin.implantacao.rat-modules.store'), [
        'name' => 'Vendas',
    ])->assertSessionHasErrors('name');
});

test('rejeita módulo com nome muito longo', function () {
    actingAsAdmin();

    $this->post(route('admin.implantacao.rat-modules.store'), [
        'name' => str_repeat('A', 101),
    ])->assertSessionHasErrors('name');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de módulo', function () {
    actingAsAdmin();
    $module = Module::factory()->create();

    $this->get(route('admin.implantacao.rat-modules.edit', $module))
        ->assertStatus(200)
        ->assertSee($module->name);
});

test('admin atualiza módulo com dados válidos', function () {
    actingAsAdmin();
    $module = Module::factory()->create(['name' => 'Antigo', 'project' => 'Geral']);

    $this->put(route('admin.implantacao.rat-modules.update', $module), [
        'name'        => 'Financeiro',
        'project'     => 'EasyPDV',
        'description' => 'Módulo financeiro',
    ])->assertRedirect(route('admin.implantacao.rat-modules.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('schedule_record_module', ['name' => 'Financeiro', 'project' => 'EasyPDV']);
    $this->assertDatabaseMissing('schedule_record_module', ['name' => 'Antigo']);
});

test('permite renomear módulo mantendo unicidade consigo mesmo', function () {
    actingAsAdmin();
    $module = Module::factory()->create(['name' => 'Meu Módulo']);

    $this->put(route('admin.implantacao.rat-modules.update', $module), [
        'name' => 'Meu Módulo',
    ])->assertRedirect(route('admin.implantacao.rat-modules.index'));
});

test('retorna 404 ao editar módulo inexistente', function () {
    actingAsAdmin();

    $this->get(route('admin.implantacao.rat-modules.edit', 9999))
        ->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove módulo sem itens vinculados', function () {
    actingAsAdmin();
    $module = Module::factory()->create();

    $this->delete(route('admin.implantacao.rat-modules.destroy', $module))
        ->assertRedirect(route('admin.implantacao.rat-modules.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('schedule_record_module', ['id' => $module->id]);
});

test('retorna warning ao tentar remover módulo com itens vinculados', function () {
    actingAsAdmin();
    $module = Module::factory()->create();
    ElementType::factory()->create(['module_id' => $module->id]);

    $this->delete(route('admin.implantacao.rat-modules.destroy', $module))
        ->assertRedirect()
        ->assertSessionHas('warning');

    $this->assertDatabaseHas('schedule_record_module', ['id' => $module->id]);
});
