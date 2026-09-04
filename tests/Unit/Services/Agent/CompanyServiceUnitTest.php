<?php

/**
 * Testes UNITÁRIOS do CompanyService — Repository mockado via interface.
 *
 * Testam exclusivamente as regras de negócio do Service sem tocar o banco.
 * Os testes de integração em CompanyServiceTest.php cobrem o fluxo completo.
 */

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use App\Services\Agent\CompanyService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function cu_service(MockInterface $repo): CompanyService
{
    return new CompanyService($repo);
}

function cu_company(): Company
{
    $c = new Company;
    $c->id = 1;

    return $c;
}

function cu_ticket(int $statusId = 1, int $id = 1): Ticket
{
    $t = new Ticket;
    $t->id = $id;
    $t->status_id = $statusId;

    return $t;
}

function cu_paginator(array $items = [], int $perPage = 15): LengthAwarePaginator
{
    $col = new \Illuminate\Database\Eloquent\Collection($items);

    return new ConcretePaginator($col, count($items), $perPage, 1);
}

// ─── getCompanyHistory — Orquestração ─────────────────────────────────────────

describe('CompanyService (unit) — getCompanyHistory', function () {

    it('modo normal chama paginateCompanyTickets (não limitCompanyTickets)', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('paginateCompanyTickets')->once()->andReturn(cu_paginator());
        $repo->shouldNotReceive('limitCompanyTickets');
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        cu_service($repo)->getCompanyHistory($company, []);
    });

    it('modo AJAX chama limitCompanyTickets com o take correto', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('limitCompanyTickets')
            ->once()
            ->with($company, Mockery::any(), 7)
            ->andReturn(new Collection);
        $repo->shouldNotReceive('paginateCompanyTickets');
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        cu_service($repo)->getCompanyHistory($company, ['mode' => true, 'take' => 7]);
    });

    it('has_more=true quando tickets retornados igualam o take', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        // 5 itens retornados, take=5 → has_more=true
        $collection = new Collection(array_fill(0, 5, cu_ticket()));
        $repo->shouldReceive('limitCompanyTickets')->andReturn($collection);
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        $result = cu_service($repo)->getCompanyHistory($company, ['mode' => true, 'take' => 5]);

        expect($result['has_more'])->toBeTrue();
    });

    it('has_more=false quando modo normal (paginação)', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        $result = cu_service($repo)->getCompanyHistory($company, []);

        expect($result['has_more'])->toBeFalse();
    });

    it('hasPending=true quando existe ticket pendente diferente do ticket atual', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $pendingTicket = cu_ticket(statusId: Ticket::STATUS_PENDING_ID, id: 99);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([3, 5]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection([$pendingTicket]));
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        // id=0 → diferente do ticket pendente id=99
        $result = cu_service($repo)->getCompanyHistory($company, ['id' => 0]);

        expect($result['pending'])->toBeTrue()
            ->and($result['pendings'])->toHaveCount(1);
    });

    it('hasPending=false quando o único ticket pendente é o ticket atual', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $pendingTicket = cu_ticket(statusId: Ticket::STATUS_PENDING_ID, id: 42);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([3, 5]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection([$pendingTicket]));
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        // id=42 → mesmo que o ticket pendente → não deve alertar
        $result = cu_service($repo)->getCompanyHistory($company, ['id' => 42]);

        expect($result['pending'])->toBeFalse()
            ->and($result['pendings'])->toHaveCount(0);
    });

    it('hasPending=false quando não há pendências', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([3]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        $result = cu_service($repo)->getCompanyHistory($company, []);

        expect($result['pending'])->toBeFalse();
    });

    it('resultado sempre contém as chaves esperadas', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        $result = cu_service($repo)->getCompanyHistory($company, []);

        expect($result)->toHaveKeys([
            'company', 'tickets', 'finalized_tickets',
            'pending', 'pendings', 'schedules', 'has_more', 'condensed',
        ]);
    });

    it('pendings é collection vazia quando hasPending=false', function () {
        $company = cu_company();
        $repo = Mockery::mock(CompanyRepositoryInterface::class);

        $repo->shouldReceive('paginateCompanyTickets')->andReturn(cu_paginator());
        $repo->shouldReceive('getTerminalStatusIds')->andReturn([]);
        $repo->shouldReceive('getPendingTickets')->andReturn(new Collection);
        $repo->shouldReceive('getFinalizedTickets')->andReturn(new Collection);
        $repo->shouldReceive('getCompanySchedules')->andReturn(new Collection);

        $result = cu_service($repo)->getCompanyHistory($company, []);

        expect($result['pendings'])->toBeEmpty();
    });

});

// ─── toggleAttribute ─────────────────────────────────────────────────────────

describe('CompanyService (unit) — toggleAttribute', function () {

    it('bloqueia alternância de situação controlada pelo financeiro', function () {
        $company = cu_company();
        $company->is_active = true;

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldNotReceive('save');

        expect(fn () => cu_service($repo)->toggleAttribute($company, 'is_active'))
            ->toThrow(DomainException::class, 'financeiro');
    });

    it('save() é chamado exatamente uma vez', function () {
        $company = cu_company();
        $company->has_crm = false;

        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('save')->once();

        cu_service($repo)->toggleAttribute($company, 'has_crm');

        $repo->shouldHaveReceived('save')->once();
    });

});

// ─── listCompanies ────────────────────────────────────────────────────────────

describe('CompanyService (unit) — listCompanies', function () {

    it('delega listWithFilters para o repository com perPage padrão 15', function () {
        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('listWithFilters')
            ->once()
            ->with(['status' => 'active'], 15)
            ->andReturn(cu_paginator());

        cu_service($repo)->listCompanies(['status' => 'active']);
    });

    it('aceita perPage customizado', function () {
        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('listWithFilters')
            ->once()
            ->with([], 30)
            ->andReturn(cu_paginator([], 30));

        cu_service($repo)->listCompanies([], 30);
    });

});

// ─── searchApi ────────────────────────────────────────────────────────────────

describe('CompanyService (unit) — searchApi', function () {

    it('delega searchForApi para o repository', function () {
        $repo = Mockery::mock(CompanyRepositoryInterface::class);
        $repo->shouldReceive('searchForApi')
            ->once()
            ->with(['q' => 'test'])
            ->andReturn(new Collection);

        cu_service($repo)->searchApi(['q' => 'test']);
    });

});
