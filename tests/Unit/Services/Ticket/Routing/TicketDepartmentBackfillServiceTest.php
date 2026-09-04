<?php

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Services\Ticket\Routing\TicketDepartmentBackfillService;

function bfs_service(): TicketDepartmentBackfillService
{
    return app(TicketDepartmentBackfillService::class);
}

function bfs_company(): Company
{
    return Company::factory()->create(['is_active' => true, 'financial_irregular' => false]);
}

function bfs_ticket(int $departmentId, ?int $categoryId, ?int $subCategoryId = null): Ticket
{
    $author = User::factory()->admin()->create();
    $company = bfs_company();
    $status = \App\Models\Ticket\Status::factory()->create();

    $cat = $categoryId ? Category::find($categoryId) : Category::factory()->create(['parent_id' => 0, 'priority' => 'low']);
    $sub = $subCategoryId
        ? Category::find($subCategoryId)
        : Category::factory()->create(['parent_id' => $cat->category_id, 'priority' => 'low']);

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

beforeEach(function () {
    Department::query()->whereRaw('LOWER(name) like ?', ['%suporte%'])->delete();
    $this->suporte = Department::factory()->create(['name' => 'Suporte Técnico Backfill']);
    $this->comercial = Department::factory()->create(['name' => 'Comercial Backfill']);
    $this->financeiro = Department::factory()->create(['name' => 'Financeiro Backfill']);
});

describe('TicketDepartmentBackfillService — dry-run', function () {

    it('não persiste alterações em modo dry-run', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);
        $ticket = bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);

        $result = bfs_service()->run(apply: false);

        expect($result['updated'])->toBe(0)
            ->and($ticket->fresh()->department_id)->toBe($this->suporte->id);
    });

    it('retorna o plano agrupado por par (de, para) com contagem', function () {
        $categoriaC = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $subC = Category::factory()->create(['parent_id' => $categoriaC->category_id, 'priority' => 'low']);

        $categoriaF = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->financeiro->id]);
        $subF = Category::factory()->create(['parent_id' => $categoriaF->category_id, 'priority' => 'low']);

        bfs_ticket($this->suporte->id, $categoriaC->category_id, $subC->category_id);
        bfs_ticket($this->suporte->id, $categoriaC->category_id, $subC->category_id);
        bfs_ticket($this->suporte->id, $categoriaF->category_id, $subF->category_id);

        $result = bfs_service()->run(apply: false);

        $countByTarget = collect($result['plan'])->keyBy('to');
        expect($countByTarget[$this->comercial->id]['count'])->toBe(2)
            ->and($countByTarget[$this->financeiro->id]['count'])->toBe(1)
            ->and($result['total'])->toBe(3);
    });

});

describe('TicketDepartmentBackfillService — apply', function () {

    it('reclassifica tickets quando aplicado e grava auditoria', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);
        $ticket = bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);
        $actor = User::factory()->admin()->create();

        $result = bfs_service()->run(apply: true, actorUserId: $actor->id);

        expect($result['updated'])->toBe(1)
            ->and($ticket->fresh()->department_id)->toBe($this->comercial->id);

        $this->assertDatabaseHas('ticketit_audits', [
            'ticket_id' => $ticket->id,
            'user_id' => $actor->id,
            'event' => 'department_backfill',
            'field' => 'department_id',
            'old_value' => (string) $this->suporte->id,
            'new_value' => (string) $this->comercial->id,
        ]);
    });

    it('não toca em tickets cuja categoria não define departamento', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => null]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low', 'department_id' => null]);
        $ticket = bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);

        $result = bfs_service()->run(apply: true);

        expect($result['updated'])->toBe(0)
            ->and($ticket->fresh()->department_id)->toBe($this->suporte->id);
    });

    it('respeita --from-department para escolher origem diferente', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);
        // Ticket em Financeiro (não em Suporte)
        $ticket = bfs_ticket($this->financeiro->id, $categoria->category_id, $sub->category_id);

        $result = bfs_service()->run(apply: true, fromDepartmentId: $this->financeiro->id);

        expect($result['updated'])->toBe(1)
            ->and($ticket->fresh()->department_id)->toBe($this->comercial->id);
    });

    it('respeita --limit', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);

        bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);
        bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);
        bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);

        $result = bfs_service()->run(apply: true, limit: 2);

        expect($result['updated'])->toBe(2)
            ->and($result['total'])->toBe(2);
    });

    it('é idempotente — segunda execução não move nada', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);
        bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);

        $first = bfs_service()->run(apply: true);
        $second = bfs_service()->run(apply: true);

        expect($first['updated'])->toBe(1)
            ->and($second['updated'])->toBe(0)
            ->and($second['total'])->toBe(0);
    });

});

describe('TicketDepartmentBackfillService — auto-detect Suporte', function () {

    it('descobre automaticamente o departamento Suporte quando from-department é null', function () {
        $categoria = Category::factory()->create(['parent_id' => 0, 'priority' => 'low', 'department_id' => $this->comercial->id]);
        $sub = Category::factory()->create(['parent_id' => $categoria->category_id, 'priority' => 'low']);
        $ticket = bfs_ticket($this->suporte->id, $categoria->category_id, $sub->category_id);

        $result = bfs_service()->run(apply: true);

        expect($result['updated'])->toBe(1)
            ->and($ticket->fresh()->department_id)->toBe($this->comercial->id);
    });

});
