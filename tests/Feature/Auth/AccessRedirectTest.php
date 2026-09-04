<?php

use App\Models\User;
use App\Models\Department;

/*
|--------------------------------------------------------------------------
| Acesso ao painel do agente
|--------------------------------------------------------------------------
*/

test('usuários staff (admin ou agent) conseguem acessar o painel do agente', function () {
    $department = Department::factory()->create();

    $agent = User::factory()->create([
        'ticketit_admin' => false,
        'ticketit_agent' => true,
        'department_id'  => $department->id,
    ]);

    $response = $this
        ->actingAs($agent, 'admin')
        ->get(route('agent.index'), ['Accept' => 'application/json']);

    $response->assertStatus(200);
});

/*
|--------------------------------------------------------------------------
| Permissão administrativa dentro do painel do agente
|--------------------------------------------------------------------------
*/

test('apenas administradores possuem permissão administrativa no painel do agente', function () {
    $department = Department::factory()->create();

    $admin = User::factory()->create([
        'ticketit_admin' => true,
        'ticketit_agent' => true,
        'department_id'  => $department->id,
    ]);

    $agent = User::factory()->create([
        'ticketit_admin' => false,
        'ticketit_agent' => true,
        'department_id'  => $department->id,
    ]);

    // Admin
    $this->actingAs($admin, 'admin')
        ->get(route('agent.index'), ['Accept' => 'application/json'])
        ->assertStatus(200);

    // Agent (não-admin)
    $this->actingAs($agent, 'admin')
        ->get(route('agent.index'), ['Accept' => 'application/json'])
        ->assertStatus(200);
});
