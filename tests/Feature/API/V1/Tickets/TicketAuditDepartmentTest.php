<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketAudit;
use App\Models\User;

function tad_setup_admin(): User
{
    return User::factory()->admin()->create();
}

function tad_setup_ticket(User $author): Ticket
{
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $status = Status::factory()->create();
    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    return Ticket::factory()->create([
        'author_id' => $author->id,
        'user_id' => $author->id,
        'company_id' => $company->id,
        'status_id' => $status->id,
        'department_id' => null,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
    ]);
}

describe('API de auditoria — eventos de departamento', function () {

    it('retorna event_label legível para department_changed', function () {
        $admin = tad_setup_admin();
        $this->actingAs($admin, 'admin');
        $ticket = tad_setup_ticket($admin);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'event' => 'department_changed',
            'operation' => 'Campo Departamento alterado',
            'field' => 'department_id',
            'old_value' => 'Suporte Técnico',
            'new_value' => 'Comercial',
        ]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/audits")
            ->assertOk()
            ->assertJsonFragment([
                'event' => 'department_changed',
                'event_label' => 'Departamento alterado',
                'field' => 'department_id',
                'field_label' => 'Departamento',
                'old_value' => 'Suporte Técnico',
                'new_value' => 'Comercial',
            ]);
    });

    it('retorna event_label legível para department_backfill', function () {
        $admin = tad_setup_admin();
        $this->actingAs($admin, 'admin');
        $ticket = tad_setup_ticket($admin);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'event' => 'department_backfill',
            'operation' => 'Reclassificação automática: department_id 1 → 3',
            'field' => 'department_id',
            'old_value' => '1',
            'new_value' => '3',
        ]);

        $this->getJson("/api/v1/tickets/{$ticket->id}/audits")
            ->assertOk()
            ->assertJsonFragment([
                'event' => 'department_backfill',
                'event_label' => 'Departamento reclassificado (backfill)',
            ]);
    });

    it('lista combina mudanças de departamento e outros campos', function () {
        $admin = tad_setup_admin();
        $this->actingAs($admin, 'admin');
        $ticket = tad_setup_ticket($admin);

        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'event' => 'status_changed',
            'operation' => 'status mudou',
            'field' => 'status_id',
            'old_value' => 'Aberto',
            'new_value' => 'Em Andamento',
        ]);
        TicketAudit::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'event' => 'department_changed',
            'operation' => 'dept mudou',
            'field' => 'department_id',
            'old_value' => 'Suporte',
            'new_value' => 'Comercial',
        ]);

        $response = $this->getJson("/api/v1/tickets/{$ticket->id}/audits");

        $response->assertOk();
        $events = collect($response->json('data'))->pluck('event')->all();
        expect($events)->toContain('status_changed', 'department_changed');
    });

});
