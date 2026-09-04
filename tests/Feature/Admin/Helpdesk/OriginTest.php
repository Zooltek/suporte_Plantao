<?php

use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de origens', function () {
    actingAsAdmin();
    Origin::factory()->count(3)->create();

    $this->get(route('admin.helpdesk.origins.index'))
        ->assertStatus(200)
        ->assertSee(Origin::first()->name);
});

test('não autenticado é redirecionado da lista de origens', function () {
    $this->get(route('admin.helpdesk.origins.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de origem', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.origins.create'))
        ->assertStatus(200);
});

test('admin cria origem com dados válidos', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.origins.store'), [
        'name'        => 'WhatsApp',
        'description' => 'Canal via WhatsApp Business',
        'status'      => '1',
    ])->assertRedirect(route('admin.helpdesk.origins.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_origin', ['name' => 'WhatsApp']);
});

test('admin cria origem sem descrição (campo opcional)', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.origins.store'), [
        'name'   => 'Telefone',
        'status' => '1',
    ])->assertRedirect(route('admin.helpdesk.origins.index'));

    $this->assertDatabaseHas('ticketit_origin', ['name' => 'Telefone']);
});

test('rejeita origem sem nome', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.origins.store'), [
        'name'   => '',
        'status' => '1',
    ])->assertSessionHasErrors('name');
});

test('rejeita origem com nome muito longo', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.origins.store'), [
        'name'   => str_repeat('A', 101),
        'status' => '1',
    ])->assertSessionHasErrors('name');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de origem', function () {
    actingAsAdmin();
    $origin = Origin::factory()->create();

    $this->get(route('admin.helpdesk.origins.edit', $origin))
        ->assertStatus(200)
        ->assertSee($origin->name);
});

test('admin atualiza origem com dados válidos', function () {
    actingAsAdmin();
    $origin = Origin::factory()->create(['name' => 'Antigo']);

    $this->put(route('admin.helpdesk.origins.update', $origin), [
        'name'        => 'E-mail',
        'description' => 'Via e-mail corporativo',
        'status'      => '1',
    ])->assertRedirect(route('admin.helpdesk.origins.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_origin', ['name' => 'E-mail']);
    $this->assertDatabaseMissing('ticketit_origin', ['name' => 'Antigo']);
});

test('retorna 404 ao editar origem inexistente', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.origins.edit', 9999))
        ->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove origem sem tickets vinculados', function () {
    actingAsAdmin();
    $origin = Origin::factory()->create();

    $this->delete(route('admin.helpdesk.origins.destroy', $origin))
        ->assertRedirect(route('admin.helpdesk.origins.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('ticketit_origin', ['id' => $origin->id]);
});

test('retorna warning ao tentar remover origem com tickets vinculados', function () {
    actingAsAdmin();

    $status   = Status::factory()->create();
    $priority = Priority::factory()->create();
    $origin   = Origin::factory()->create();
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
        'origin_id'   => $origin->id,
        'user_id'     => $user->id,
        'agent_id'    => $user->id,
        'category_id' => $categoryId,
        'company_id'  => 1,
        'created_at'  => now(),
        'updated_at'  => now(),
    ]);

    $this->delete(route('admin.helpdesk.origins.destroy', $origin))
        ->assertRedirect()
        ->assertSessionHas('warning');
});
