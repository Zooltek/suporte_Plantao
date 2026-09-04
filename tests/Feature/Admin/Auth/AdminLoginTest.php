<?php

use App\Models\User;

/*
|--------------------------------------------------------------------------
| Tela de login admin
|--------------------------------------------------------------------------
*/

test('exibe a tela de login do admin', function () {
    $this->get(route('admin.login'))
        ->assertStatus(200);
});

test('redireciona admin autenticado que tenta acessar login', function () {
    actingAsAdmin();

    $this->get(route('admin.login'))
        ->assertRedirect();
});

/*
|--------------------------------------------------------------------------
| Tentativas de login
|--------------------------------------------------------------------------
*/

test('retorna erro com credenciais inválidas', function () {
    User::factory()->create(['email' => 'admin@test.com']);

    $this->post(route('login'), [
        'email'    => 'admin@test.com',
        'password' => 'senha-errada',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('admin faz login com credenciais válidas', function () {
    $admin = User::factory()->admin()->create([
        'email' => 'admin@login.com',
    ]);

    $this->post('/admin/login', [
        'email'    => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin, 'admin');
});

test('agente válido loga e é redirecionado para agent.index', function () {
    $agent = User::factory()->agent()->create([
        'email' => 'agent@login.com',
    ]);

    $this->post('/admin/login', [
        'email'    => $agent->email,
        'password' => 'password',
    ])->assertRedirect(route('agent.index'));

    $this->assertAuthenticatedAs($agent, 'admin');
});

test('usuário do departamento CRM loga mesmo sem ser staff', function () {
    // AccessService::isCrmDepartment depende da flag is_crm na user_department
    \Illuminate\Support\Facades\DB::table('user_department')->updateOrInsert(
        ['id' => 3],
        ['name' => 'CRM / Comercial', 'is_crm' => true, 'is_feedback' => true, 'created_at' => now(), 'updated_at' => now()],
    );

    $crmUser = User::factory()->create([
        'email'         => 'crm@test.com',
        'department_id' => 3,
        'ticketit_admin' => false,
        'ticketit_agent' => false,
    ]);

    $this->post('/admin/login', [
        'email'    => $crmUser->email,
        'password' => 'password',
    ])->assertRedirect(route('crm.index'));

    $this->assertAuthenticatedAs($crmUser, 'admin');
});

test('bloqueia login de usuário sem permissão, mesmo com senha correta', function () {
    $user = User::factory()->create([
        'email'          => 'sempermissao@test.com',
        'ticketit_admin' => false,
        'ticketit_agent' => false,
        'department_id'  => 2,
    ]);

    $this->post('/admin/login', [
        'email'    => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest('admin');
});

test('retorna erro com e-mail inexistente', function () {
    $this->post(route('login'), [
        'email'    => 'naoexiste@test.com',
        'password' => 'qualquer',
    ])->assertSessionHasErrors('email');
});

test('valida que e-mail é obrigatório', function () {
    $this->post(route('login'), [
        'email'    => '',
        'password' => 'password',
    ])->assertSessionHasErrors('email');
});

test('valida que senha é obrigatória', function () {
    $this->post(route('login'), [
        'email'    => 'admin@test.com',
        'password' => '',
    ])->assertSessionHasErrors('password');
});

/*
|--------------------------------------------------------------------------
| Acesso ao painel admin autenticado
|--------------------------------------------------------------------------
*/

test('admin autenticado acessa o dashboard', function () {
    actingAsAdmin();

    $this->get(route('admin.dashboard'))
        ->assertStatus(200);
});

test('usuário não autenticado é redirecionado do dashboard', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect();
});

test('agente sem ticketit_admin é barrado do dashboard admin', function () {
    actingAsAgent();

    // O middleware helpdesk.admin redireciona agentes para agent.index
    $this->get(route('admin.helpdesk.status.index'))
        ->assertRedirect(route('agent.index'));
});

/*
|--------------------------------------------------------------------------
| Logout
|--------------------------------------------------------------------------
*/

test('admin pode fazer logout', function () {
    actingAsAdmin();

    $this->post(route('admin.logout'))
        ->assertRedirect('/');

    $this->assertGuest('admin');
});
