<?php

use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de prioridades', function () {
    actingAsAdmin();
    Priority::factory()->count(3)->create();

    $this->get(route('admin.helpdesk.priority.index'))
        ->assertStatus(200)
        ->assertSee(Priority::first()->name);
});

test('não autenticado é redirecionado da lista de prioridades', function () {
    $this->get(route('admin.helpdesk.priority.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de prioridade', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.priority.create'))
        ->assertStatus(200);
});

test('admin cria prioridade com dados válidos', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.priority.store'), [
        'name'  => 'Urgente',
        'color' => '#ef4444',
    ])->assertRedirect(route('admin.helpdesk.priority.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_priorities', [
        'name'  => 'Urgente',
        'color' => '#ef4444',
    ]);
});

test('rejeita prioridade sem nome', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.priority.store'), [
        'name'  => '',
        'color' => '#ef4444',
    ])->assertSessionHasErrors('name');
});

test('rejeita prioridade com cor inválida', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.priority.store'), [
        'name'  => 'Alta',
        'color' => 'vermelho',
    ])->assertSessionHasErrors('color');
});

test('rejeita prioridade sem cor', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.priority.store'), [
        'name'  => 'Alta',
        'color' => '',
    ])->assertSessionHasErrors('color');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de prioridade', function () {
    actingAsAdmin();
    $priority = Priority::factory()->create();

    $this->get(route('admin.helpdesk.priority.edit', $priority))
        ->assertStatus(200)
        ->assertSee($priority->name);
});

test('admin atualiza prioridade com dados válidos', function () {
    actingAsAdmin();
    $priority = Priority::factory()->create(['name' => 'Baixa', 'color' => '#aabbcc']);

    $this->put(route('admin.helpdesk.priority.update', $priority), [
        'name'  => 'Crítica',
        'color' => '#dc2626',
    ])->assertRedirect(route('admin.helpdesk.priority.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_priorities', ['name' => 'Crítica', 'color' => '#dc2626']);
    $this->assertDatabaseMissing('ticketit_priorities', ['name' => 'Baixa']);
});

test('rejeita atualização com nome vazio', function () {
    actingAsAdmin();
    $priority = Priority::factory()->create();

    $this->put(route('admin.helpdesk.priority.update', $priority), [
        'name'  => '',
        'color' => '#dc2626',
    ])->assertSessionHasErrors('name');
});

test('retorna 404 ao editar prioridade inexistente', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.priority.edit', 9999))
        ->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove prioridade sem tickets vinculados', function () {
    actingAsAdmin();
    $priority = Priority::factory()->create();

    $this->delete(route('admin.helpdesk.priority.destroy', $priority))
        ->assertRedirect(route('admin.helpdesk.priority.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('ticketit_priorities', ['id' => $priority->id]);
});

test('retorna warning ao tentar remover prioridade com tickets vinculados', function () {
    actingAsAdmin();

    $status   = Status::factory()->create();
    $priority = Priority::factory()->create();
    $user     = \App\Models\User::factory()->agent()->create();

    $categoryId = DB::table('ticketit_categories')->insertGetId([
        'name'  => 'Categoria Teste',
        'color' => '#000000',
    ]);

    DB::table('ticketit')->insert([
        'subject'     => 'Ticket de teste',
        'content'     => 'Conteúdo',
        'status_id'   => $status->id,
        'priority_id' => $priority->id,
        'user_id'     => $user->id,
        'agent_id'    => $user->id,
        'category_id' => $categoryId,
        'company_id'  => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $this->delete(route('admin.helpdesk.priority.destroy', $priority))
        ->assertRedirect()
        ->assertSessionHas('warning');
});
