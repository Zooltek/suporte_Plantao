<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Ticket\Routing\TicketDepartmentTransferNotifier;

function tdtn_notifier(): TicketDepartmentTransferNotifier
{
    return app(TicketDepartmentTransferNotifier::class);
}

function tdtn_ticket(?int $departmentId): Ticket
{
    $author = User::factory()->admin()->create();
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $status = Status::factory()->create();
    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    return Ticket::factory()->create([
        'author_id' => $author->id,
        'user_id' => $author->id,
        'company_id' => $company->id,
        'status_id' => $status->id,
        'department_id' => $departmentId,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
    ]);
}

describe('TicketDepartmentTransferNotifier', function () {

    it('cria uma notificação por agente ativo do novo departamento', function () {
        $oldDept = Department::factory()->create(['name' => 'Dept Antigo TNT']);
        $newDept = Department::factory()->create(['name' => 'Dept Novo TNT']);

        $agentA = User::factory()->agent()->create(['department_id' => $newDept->id, 'active' => true]);
        $agentB = User::factory()->agent()->create(['department_id' => $newDept->id, 'active' => true]);
        User::factory()->agent()->create(['department_id' => $oldDept->id, 'active' => true]);

        $ticket = tdtn_ticket($oldDept->id);

        tdtn_notifier()->notify($ticket, $oldDept->id, $newDept->id);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $agentA->id, 'status' => 1]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $agentB->id, 'status' => 1]);
    });

    it('não notifica o ator (quem fez a mudança)', function () {
        $newDept = Department::factory()->create(['name' => 'Dept Ator']);
        $actor = User::factory()->admin()->create(['department_id' => $newDept->id, 'active' => true]);
        $otherAgent = User::factory()->agent()->create(['department_id' => $newDept->id, 'active' => true]);

        $this->actingAs($actor, 'admin');

        $ticket = tdtn_ticket($newDept->id);

        tdtn_notifier()->notify($ticket, null, $newDept->id);

        $this->assertDatabaseMissing('user_notifications', ['user_id' => $actor->id]);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $otherAgent->id]);
    });

    it('não cria notificações quando o novo departamento é nulo', function () {
        $dept = Department::factory()->create(['name' => 'Dept Drop']);
        $agent = User::factory()->agent()->create(['department_id' => $dept->id, 'active' => true]);
        $ticket = tdtn_ticket($dept->id);

        tdtn_notifier()->notify($ticket, $dept->id, null);

        $this->assertDatabaseMissing('user_notifications', ['user_id' => $agent->id]);
    });

    it('não cria notificações quando old e new são iguais', function () {
        $dept = Department::factory()->create(['name' => 'Mesmo Dept']);
        $agent = User::factory()->agent()->create(['department_id' => $dept->id, 'active' => true]);
        $ticket = tdtn_ticket($dept->id);

        tdtn_notifier()->notify($ticket, $dept->id, $dept->id);

        $this->assertDatabaseMissing('user_notifications', ['user_id' => $agent->id]);
    });

    it('ignora agentes inativos do novo departamento', function () {
        $dept = Department::factory()->create(['name' => 'Dept Inativos']);
        $ativo = User::factory()->agent()->create(['department_id' => $dept->id, 'active' => true]);
        $inativo = User::factory()->agent()->create(['department_id' => $dept->id, 'active' => false]);
        $ticket = tdtn_ticket(null);

        tdtn_notifier()->notify($ticket, null, $dept->id);

        $this->assertDatabaseHas('user_notifications', ['user_id' => $ativo->id]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $inativo->id]);
    });

});

describe('TicketService::quickUpdateDepartment — integração com notifier', function () {

    it('disparar quickUpdateDepartment notifica agentes do novo setor', function () {
        $admin = User::factory()->admin()->create();
        $newDept = Department::factory()->create(['name' => 'Dept Quick Update']);
        $agent = User::factory()->agent()->create(['department_id' => $newDept->id, 'active' => true]);
        $ticket = tdtn_ticket(null);

        $this->actingAs($admin, 'admin');

        app(\App\Services\Agent\TicketService::class)
            ->quickUpdateDepartment($ticket, $newDept->id);

        expect($ticket->fresh()->department_id)->toBe($newDept->id);
        $this->assertDatabaseHas('user_notifications', ['user_id' => $agent->id, 'status' => 1]);
    });

});
