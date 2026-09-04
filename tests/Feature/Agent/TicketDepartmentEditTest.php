<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

function tde_setup_ticket(?int $departmentId = null): Ticket
{
    $admin = User::factory()->admin()->create();
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $status = Status::factory()->create();
    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    return Ticket::factory()->create([
        'author_id' => $admin->id,
        'user_id' => $admin->id,
        'company_id' => $company->id,
        'status_id' => $status->id,
        'department_id' => $departmentId,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
    ]);
}

describe('Quick update do departamento — autorização', function () {

    it('admin consegue alterar o departamento de um ticket', function () {
        $admin = User::factory()->admin()->create();
        $novoDept = Department::factory()->create(['name' => 'Novo Setor Admin']);
        $ticket = tde_setup_ticket();

        $this->actingAs($admin, 'admin')
            ->patch(route('agent.ticket.quick-update', $ticket), [
                'department_id' => $novoDept->id,
            ])
            ->assertRedirect(route('agent.ticket.show', $ticket->id));

        expect($ticket->fresh()->department_id)->toBe($novoDept->id);
    });

    it('agente comum recebe erro de validação ao tentar alterar o departamento', function () {
        $agent = User::factory()->agent()->create();
        $novoDept = Department::factory()->create(['name' => 'Tentativa Agente']);
        $ticket = tde_setup_ticket($agent->department_id);

        $this->actingAs($agent, 'admin');

        $ticket->agent_id = $agent->id;
        $ticket->save();

        $this->patch(route('agent.ticket.quick-update', $ticket), [
            'department_id' => $novoDept->id,
        ])->assertSessionHasErrors('department_id');

        expect($ticket->fresh()->department_id)->toBe($agent->department_id);
    });

    it('alteração via quick update gera registro de auditoria', function () {
        $admin = User::factory()->admin()->create();
        $deptAntigo = Department::factory()->create(['name' => 'Antigo Audit']);
        $deptNovo = Department::factory()->create(['name' => 'Novo Audit']);
        $ticket = tde_setup_ticket($deptAntigo->id);

        $this->actingAs($admin, 'admin')
            ->patch(route('agent.ticket.quick-update', $ticket), [
                'department_id' => $deptNovo->id,
            ]);

        $this->assertDatabaseHas('ticketit_audits', [
            'ticket_id' => $ticket->id,
            'event' => 'department_changed',
            'field' => 'department_id',
            'new_value' => $deptNovo->name,
        ]);
    });

    it('admin pode limpar o departamento (set null)', function () {
        $admin = User::factory()->admin()->create();
        $dept = Department::factory()->create(['name' => 'Será Limpo']);
        $ticket = tde_setup_ticket($dept->id);

        $this->actingAs($admin, 'admin')
            ->patch(route('agent.ticket.quick-update', $ticket), [
                'department_id' => null,
            ])
            ->assertRedirect();

        expect($ticket->fresh()->department_id)->toBeNull();
    });

});

describe('Filtro de departamento na listagem', function () {

    it('admin recebe a lista de departamentos para filtrar', function () {
        $admin = User::factory()->admin()->create();
        Department::factory()->create(['name' => 'Filtro Admin Dept']);

        $response = $this->actingAs($admin, 'admin')->get(route('agent.ticket.index'));

        $response->assertOk();
        $response->assertViewHas('departments', fn ($depts) => $depts->isNotEmpty());
    });

    it('agente comum não recebe a lista de departamentos para filtrar', function () {
        $agent = User::factory()->agent()->create();

        $response = $this->actingAs($agent, 'admin')->get(route('agent.ticket.index'));

        $response->assertOk();
        $response->assertViewHas('departments', fn ($depts) => $depts->isEmpty());
    });

});
