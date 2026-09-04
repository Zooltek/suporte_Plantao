<?php

/**
 * Testes UNITÁRIOS do ImplantacaoService — Repository mockado via interface.
 *
 * Testam exclusivamente as regras de negócio do Service sem tocar o banco.
 * Como o Repository é mockado, estes testes NÃO dependem do MySQL e
 * passam normalmente sobre SQLite.
 */

use App\Contracts\Repositories\ImplantacaoRepositoryInterface;
use App\Models\Company;
use App\Services\Agent\ImplantacaoService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator as ConcretePaginator;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function isu_service(MockInterface $repo): ImplantacaoService
{
    return new ImplantacaoService($repo);
}

function isu_repo(): MockInterface
{
    return Mockery::mock(ImplantacaoRepositoryInterface::class);
}

function isu_paginator(array $items = [], int $total = 0, int $perPage = 20): ConcretePaginator
{
    return new ConcretePaginator(
        new Collection($items),
        $total,
        $perPage,
        1
    );
}

// ─── getStats() ───────────────────────────────────────────────────────────────

describe('ImplantacaoService (unit) — getStats()', function () {

    it('retorna as chaves esperadas no array', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(0);

        $stats = isu_service($repo)->getStats();

        expect($stats)->toHaveKeys([
            'totalClients',
            'totalSchedules',
            'totalOpenTickets',
            'totalMinutes',
            'totalHoursFormatted',
        ]);
    });

    it('totalClients conta apenas IDs únicos', function () {
        $repo = isu_repo();
        // Mesmo cliente com 2 records ativos — deve contar como 1
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([5, 5, 7]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(2);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(0);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalClients'])->toBe(2);
    });

    it('totalHoursFormatted retorna "—" quando totalMinutes é zero', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(0);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalHoursFormatted'])->toBe('—');
    });

    it('totalHoursFormatted formata minutos em horas e minutos', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([1]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(125); // 2h 05min

        $stats = isu_service($repo)->getStats();

        expect($stats['totalHoursFormatted'])->toBe('2h 05min');
        expect($stats['totalMinutes'])->toBe(125);
    });

    it('totalHoursFormatted formata apenas minutos quando menos de 1h', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([1]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(45);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalHoursFormatted'])->toBe('45min');
    });

    it('delega totalSchedules ao repository', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([1, 2]);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(7);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(0);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalSchedules'])->toBe(7);
    });

    it('delega totalOpenTickets ao repository passando os clientIds', function () {
        $clientIds = [10, 20, 30];

        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn($clientIds);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->with($clientIds)->andReturn(3);
        $repo->shouldReceive('getTotalMinutes')->once()->andReturn(0);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalOpenTickets'])->toBe(3);
    });

    it('passa os clientIds corretos para getTotalMinutes', function () {
        $clientIds = [1, 2];

        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn($clientIds);
        $repo->shouldReceive('countActiveSchedules')->once()->andReturn(0);
        $repo->shouldReceive('getOpenTicketsCount')->once()->andReturn(0);
        $repo->shouldReceive('getTotalMinutes')->once()->with($clientIds)->andReturn(60);

        $stats = isu_service($repo)->getStats();

        expect($stats['totalMinutes'])->toBe(60);
        expect($stats['totalHoursFormatted'])->toBe('1h 00min');
    });

});

// ─── getClients() ─────────────────────────────────────────────────────────────

describe('ImplantacaoService (unit) — getClients()', function () {

    it('solicita clientIds ao repository e os repassa para getClientsWithDetails', function () {
        $clientIds = [1, 2, 3];

        $company          = new Company();
        $company->id      = 1;
        $company->name    = 'Teste SA';
        $company->active_records = 2;
        $company->total_minutes  = 0;

        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn($clientIds);
        $repo->shouldReceive('getClientsWithDetails')->once()->with($clientIds)->andReturn(new Collection([$company]));

        $result = isu_service($repo)->getClients();

        expect($result)->toHaveCount(1);
    });

    it('adiciona total_hours_formatted com "—" quando total_minutes é 0', function () {
        $company                 = new Company();
        $company->id             = 1;
        $company->name           = 'Alpha SA';
        $company->active_records = 1;
        $company->total_minutes  = 0;

        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([1]);
        $repo->shouldReceive('getClientsWithDetails')->once()->andReturn(new Collection([$company]));

        $result = isu_service($repo)->getClients();

        expect($result->first()->total_hours_formatted)->toBe('—');
    });

    it('adiciona total_hours_formatted formatado quando total_minutes > 0', function () {
        $company                 = new Company();
        $company->id             = 1;
        $company->name           = 'Beta Ltda';
        $company->active_records = 1;
        $company->total_minutes  = 90; // 1h 30min

        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([1]);
        $repo->shouldReceive('getClientsWithDetails')->once()->andReturn(new Collection([$company]));

        $result = isu_service($repo)->getClients();

        expect($result->first()->total_hours_formatted)->toBe('1h 30min');
    });

    it('retorna coleção vazia quando não há clientIds', function () {
        $repo = isu_repo();
        $repo->shouldReceive('getImplementationClientIds')->once()->andReturn([]);
        $repo->shouldReceive('getClientsWithDetails')->once()->andReturn(new Collection());

        $result = isu_service($repo)->getClients();

        expect($result)->toHaveCount(0);
    });

});

// ─── getSchedules() ───────────────────────────────────────────────────────────

describe('ImplantacaoService (unit) — getSchedules()', function () {

    it('delega ao repository com perPage padrão 20', function () {
        $paginator = isu_paginator(perPage: 20);

        $repo = isu_repo();
        $repo->shouldReceive('getSchedulesPaginated')->once()->with(20)->andReturn($paginator);

        $result = isu_service($repo)->getSchedules();

        expect($result->perPage())->toBe(20);
    });

    it('delega ao repository com perPage customizado', function () {
        $paginator = isu_paginator(perPage: 5);

        $repo = isu_repo();
        $repo->shouldReceive('getSchedulesPaginated')->once()->with(5)->andReturn($paginator);

        $result = isu_service($repo)->getSchedules(perPage: 5);

        expect($result->perPage())->toBe(5);
    });

    it('retorna exatamente o que o repository entrega', function () {
        $paginator = isu_paginator(total: 7, perPage: 20);

        $repo = isu_repo();
        $repo->shouldReceive('getSchedulesPaginated')->once()->andReturn($paginator);

        $result = isu_service($repo)->getSchedules();

        expect($result->total())->toBe(7);
    });

});
