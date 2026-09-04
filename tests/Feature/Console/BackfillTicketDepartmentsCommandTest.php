<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use App\Models\User;

function bfcmd_setup(): array
{
    Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
    $suporte = Department::factory()->create(['name' => 'Suporte Técnico CMD']);
    $comercial = Department::factory()->create(['name' => 'Comercial CMD']);

    $admin = User::factory()->admin()->create();
    $company = Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
    $status = Status::factory()->create();

    $cat = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $comercial->id]);
    $sub = Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

    $ticket = Ticket::factory()->create([
        'author_id' => $admin->id,
        'user_id' => $admin->id,
        'company_id' => $company->id,
        'status_id' => $status->id,
        'department_id' => $suporte->id,
        'category_id' => $cat->category_id,
        'sub_category_id' => $sub->category_id,
    ]);

    return compact('suporte', 'comercial', 'admin', 'ticket');
}

it('comando em dry-run não persiste alterações', function () {
    ['suporte' => $suporte, 'ticket' => $ticket] = bfcmd_setup();

    $this->artisan('tickets:backfill-departments')
        ->expectsOutputToContain('modo: DRY-RUN')
        ->expectsOutputToContain('Nenhuma alteração persistida')
        ->assertExitCode(0);

    expect($ticket->fresh()->department_id)->toBe($suporte->id);
});

it('comando com --apply reclassifica e grava auditoria', function () {
    ['comercial' => $comercial, 'admin' => $admin, 'ticket' => $ticket] = bfcmd_setup();

    $this->artisan('tickets:backfill-departments', ['--apply' => true, '--actor' => $admin->id])
        ->expectsOutputToContain('modo: APLICAR')
        ->assertExitCode(0);

    expect($ticket->fresh()->department_id)->toBe($comercial->id);
    $this->assertDatabaseHas('ticketit_audits', [
        'ticket_id' => $ticket->id,
        'event' => 'department_backfill',
        'field' => 'department_id',
        'new_value' => (string) $comercial->id,
    ]);
});
