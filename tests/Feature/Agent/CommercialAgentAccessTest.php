<?php

use App\Models\Department;
use App\Models\User;

/**
 * Cobre a liberação do agent panel para usuários do departamento Comercial
 * (is_crm=true) que também são staff (ticketit_agent / role agent).
 *
 * Antes desta feature, qualquer usuário com department.is_crm=true era
 * redirecionado para crm.index após o login, mesmo tendo permissão de
 * agente — impedindo-os de visualizar os chamados (tickets) que vinham
 * pelo WhatsApp endereçados ao setor Comercial.
 */
function ensureCrmDeptForRoutingTest(int $id = 3): Department
{
    $dept = Department::find($id);

    if (! $dept) {
        \Illuminate\Support\Facades\DB::table('user_department')->insert([
            'id' => $id,
            'name' => 'CRM / Comercial',
            'is_default' => false,
            'is_crm' => true,
            'is_feedback' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Department::findOrFail($id);
    }

    $dept->is_crm = true;
    $dept->is_feedback = true;
    $dept->save();

    return $dept;
}

it('usuário do Comercial com ticketit_agent é redirecionado para agent.index após login admin', function () {
    ensureCrmDeptForRoutingTest();

    $user = User::factory()->agent()->create([
        'department_id' => 3,
        'password' => bcrypt('senha-secreta'),
        'email' => 'comercial.agente@example.com',
    ]);

    $response = $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
        ->post('/admin/login', [
            'email' => $user->email,
            'password' => 'senha-secreta',
        ]);

    $response->assertRedirect(route('agent.index'));
    expect(auth('admin')->id())->toBe($user->id);
});

it('usuário apenas Comercial (sem ticketit_agent) continua indo para crm.index após login admin', function () {
    ensureCrmDeptForRoutingTest();

    $user = User::factory()->create([
        'department_id' => 3,
        'ticketit_agent' => false,
        'ticketit_admin' => false,
        'password' => bcrypt('senha-secreta'),
        'email' => 'comercial.feedback@example.com',
    ]);

    $response = $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class)
        ->post('/admin/login', [
            'email' => $user->email,
            'password' => 'senha-secreta',
        ]);

    $response->assertRedirect(route('crm.index'));
});

it('usuário do Comercial autenticado acessa agent.ticket.index sem ser redirecionado', function () {
    ensureCrmDeptForRoutingTest();

    $user = User::factory()->agent()->create(['department_id' => 3]);
    $this->actingAs($user, 'admin');

    $this->get(route('agent.ticket.index'))->assertOk();
});

it('usuário apenas Comercial é barrado pelo AgentMiddleware e redirecionado para crm.index', function () {
    ensureCrmDeptForRoutingTest();

    $user = User::factory()->create([
        'department_id' => 3,
        'ticketit_agent' => false,
        'ticketit_admin' => false,
    ]);
    $this->actingAs($user, 'admin');

    $this->get(route('agent.ticket.index'))
        ->assertRedirect(route('crm.index'));
});
