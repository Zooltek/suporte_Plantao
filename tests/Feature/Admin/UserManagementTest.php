<?php

use App\Models\Department;
use App\Models\User;
use Symfony\Component\DomCrawler\Crawler;

test('admin pode alterar o departamento de um agente sem invalidar o perfil de acesso', function () {
    actingAsAdmin();

    $currentDepartment = Department::factory()->create();
    $newDepartment = Department::factory()->create();

    $agent = User::factory()->agent()->create([
        'department_id' => $currentDepartment->id,
        'ticketit_admin' => false,
        'ticketit_agent' => true,
    ]);

    $this->putJson(route('admin.users.api.update', $agent), [
        'name' => $agent->name,
        'email' => $agent->email,
        'role' => '1',
        'department_id' => (string) $newDepartment->id,
        'active' => true,
        'deployment_admin' => false,
        'can_manage_implementation' => false,
    ])->assertOk()
      ->assertJsonPath('user.department.id', $newDepartment->id)
      ->assertJsonPath('user.department.name', $newDepartment->name);

    $this->assertDatabaseHas('users', [
        'id' => $agent->id,
        'department_id' => $newDepartment->id,
        'ticketit_agent' => 1,
        'ticketit_admin' => 0,
    ]);
});

test('rejeita role inválido (string vazia) na atualização de usuário', function () {
    actingAsAdmin();

    $dept  = Department::factory()->create();
    $agent = User::factory()->agent()->create(['department_id' => $dept->id]);

    $this->putJson(route('admin.users.api.update', $agent), [
        'name'                      => $agent->name,
        'email'                     => $agent->email,
        'role'                      => '',
        'department_id'             => (string) $dept->id,
        'active'                    => true,
        'deployment_admin'          => false,
        'can_manage_implementation' => false,
    ])->assertUnprocessable()
      ->assertJsonValidationErrors('role');
});

test('atualização de usuário sem flags de acesso com role=1 define ticketit_agent=1', function () {
    actingAsAdmin();

    $dept = Department::factory()->create();

    // Usuário sem flags (como ficaria após corrupção anterior do bug)
    $user = User::factory()->create([
        'department_id'  => $dept->id,
        'ticketit_admin' => false,
        'ticketit_agent' => false,
    ]);

    $newDept = Department::factory()->create();

    $this->putJson(route('admin.users.api.update', $user), [
        'name'                      => $user->name,
        'email'                     => $user->email,
        'role'                      => 1,
        'department_id'             => $newDept->id,
        'active'                    => true,
        'deployment_admin'          => false,
        'can_manage_implementation' => false,
    ])->assertOk();

    $this->assertDatabaseHas('users', [
        'id'             => $user->id,
        'department_id'  => $newDept->id,
        'ticketit_agent' => 1,
        'ticketit_admin' => 0,
    ]);
});

test('listagem de usuários exibe crm comercial para usuários do departamento crm', function () {
    actingAsAdmin();

    $crmDepartment = Department::query()->forceCreate([
        'id' => 3,
        'name' => 'CRM / Comercial',
    ]);

    $crmUser = User::factory()->crm()->create([
        'department_id' => $crmDepartment->id,
        'ticketit_admin' => false,
        'ticketit_agent' => false,
        'name' => 'Usuário CRM',
    ]);

    $response = $this->get(route('admin.users.index'))->assertOk();

    $row = (new Crawler($response->getContent()))
        ->filter('tbody tr')
        ->reduce(fn (Crawler $node) => str_contains($node->text(), $crmUser->name));

    expect($row->count())->toBe(1)
        ->and(trim(preg_replace('/\s+/', ' ', $row->eq(0)->filter('td')->eq(3)->text())))->toBe('CRM / Comercial');
});
