<?php

use App\Models\Ticket\Ticket;
use App\Models\Ticket\Status;
use App\Services\Admin\Helpdesk\HelpdeskService;

/*
|--------------------------------------------------------------------------
| Listagem
|--------------------------------------------------------------------------
*/

test('admin vê a lista de status', function () {
    actingAsAdmin();
    Status::factory()->count(3)->create();

    $this->get(route('admin.helpdesk.status.index'))
        ->assertStatus(200)
        ->assertSee(Status::first()->name);
});

test('não autenticado é redirecionado da lista de status', function () {
    $this->get(route('admin.helpdesk.status.index'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Criar
|--------------------------------------------------------------------------
*/

test('exibe formulário de criação de status', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.status.create'))
        ->assertStatus(200);
});

test('admin cria status com dados válidos', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.status.store'), [
        'name'  => 'Em Análise',
        'color' => '#6366f1',
    ])->assertRedirect(route('admin.helpdesk.status.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_statuses', [
        'name'  => 'Em Análise',
        'color' => '#6366f1',
    ]);
});

test('rejeita status sem nome', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.status.store'), [
        'name'  => '',
        'color' => '#6366f1',
    ])->assertSessionHasErrors('name');
});

test('rejeita status com cor inválida', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.status.store'), [
        'name'  => 'Teste',
        'color' => 'nao-e-hex',
    ])->assertSessionHasErrors('color');
});

test('rejeita status sem cor', function () {
    actingAsAdmin();

    $this->post(route('admin.helpdesk.status.store'), [
        'name'  => 'Teste',
        'color' => '',
    ])->assertSessionHasErrors('color');
});

test('rejeita criação de status duplicado ignorando caixa e espaços', function () {
    actingAsAdmin();
    Status::factory()->create(['name' => 'Em Andamento']);

    $this->post(route('admin.helpdesk.status.store'), [
        'name' => '  em andamento  ',
        'color' => '#334155',
    ])->assertSessionHasErrors('name');
});

/*
|--------------------------------------------------------------------------
| Editar / Atualizar
|--------------------------------------------------------------------------
*/

test('exibe formulário de edição de status', function () {
    actingAsAdmin();
    $status = Status::factory()->create();

    $this->get(route('admin.helpdesk.status.edit', $status))
        ->assertStatus(200)
        ->assertSee($status->name);
});

test('admin atualiza status com dados válidos', function () {
    actingAsAdmin();
    $status = Status::factory()->create(['name' => 'Antigo', 'color' => '#000000']);

    $this->put(route('admin.helpdesk.status.update', $status), [
        'name'  => 'Novo Nome',
        'color' => '#ff5500',
    ])->assertRedirect(route('admin.helpdesk.status.index'))
      ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_statuses', ['name' => 'Novo Nome', 'color' => '#ff5500']);
    $this->assertDatabaseMissing('ticketit_statuses', ['name' => 'Antigo']);
});

test('rejeita atualização com nome vazio', function () {
    actingAsAdmin();
    $status = Status::factory()->create();

    $this->put(route('admin.helpdesk.status.update', $status), [
        'name'  => '',
        'color' => '#6366f1',
    ])->assertSessionHasErrors('name');
});

test('rejeita atualização para nome já usado por outro status', function () {
    actingAsAdmin();
    $status = Status::factory()->create(['name' => 'Pendente']);
    Status::factory()->create(['name' => 'Em Andamento']);

    $this->put(route('admin.helpdesk.status.update', $status), [
        'name' => ' em andamento ',
        'color' => '#6366f1',
    ])->assertSessionHasErrors('name');
});

test('permite atualizar status preservando o mesmo nome normalizado', function () {
    actingAsAdmin();
    $status = Status::factory()->create(['name' => 'Status Exclusivo', 'color' => '#111111']);

    $this->put(route('admin.helpdesk.status.update', $status), [
        'name' => '  Status Exclusivo ',
        'color' => '#222222',
    ])->assertRedirect(route('admin.helpdesk.status.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('ticketit_statuses', [
        'id' => $status->id,
        'name' => 'Status Exclusivo',
        'color' => '#222222',
    ]);
});

test('retorna 404 ao editar status inexistente', function () {
    actingAsAdmin();

    $this->get(route('admin.helpdesk.status.edit', 9999))
        ->assertStatus(404);
});

/*
|--------------------------------------------------------------------------
| Excluir
|--------------------------------------------------------------------------
*/

test('admin remove status sem tickets vinculados', function () {
    actingAsAdmin();
    $status = Status::factory()->create();

    $this->delete(route('admin.helpdesk.status.destroy', $status))
        ->assertRedirect(route('admin.helpdesk.status.index'))
        ->assertSessionHas('status');

    $this->assertDatabaseMissing('ticketit_statuses', ['id' => $status->id]);
});

test('não permite remover status com tickets vinculados', function () {
    actingAsAdmin();
    $status = Status::factory()->create();
    Ticket::factory()->create(['status_id' => $status->id]);

    $service = app(HelpdeskService::class);

    expect(fn () => $service->deleteStatus($status->id))
        ->toThrow(\DomainException::class, 'Não é possível remover um status que possui chamados vinculados.');

    $this->assertDatabaseHas('ticketit_statuses', ['id' => $status->id]);
});

test('retorna warning ao tentar remover status com tickets via HTTP', function () {
    actingAsAdmin();

    $status   = Status::factory()->create();
    $priority = \App\Models\Ticket\Priority::factory()->create();
    $user     = \App\Models\User::factory()->agent()->create();

    // Cria categoria e ticket diretamente (sem factory) para testar a constraint
    $categoryId = \Illuminate\Support\Facades\DB::table('ticketit_categories')->insertGetId([
        'name'  => 'Categoria Teste',
        'color' => '#000000',
    ]);

    \Illuminate\Support\Facades\DB::table('ticketit')->insert([
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

    $this->delete(route('admin.helpdesk.status.destroy', $status))
        ->assertRedirect()
        ->assertSessionHas('warning');
});
