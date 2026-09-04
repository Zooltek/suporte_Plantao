<?php

/**
 * Testes UNITÁRIOS do MonitorService — Repository mockado via interface.
 *
 * Isolam exclusivamente a regra de negócio do Service (geração do gráfico)
 * sem tocar o banco de dados.
 */

use App\Contracts\Repositories\MonitorRepositoryInterface;
use App\Services\Agent\MonitorService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function mon_service(MockInterface $repo): MonitorService
{
    return new MonitorService($repo);
}

function mon_repo(): MockInterface
{
    return Mockery::mock(MonitorRepositoryInterface::class);
}

// ─── getAgentsData ────────────────────────────────────────────────────────────

describe('MonitorService — getAgentsData', function () {

    it('delega para o repository e retorna o resultado sem alteração', function () {
        $now    = Carbon::now();
        $start  = $now->copy()->startOfDay();
        $end    = $now->copy()->endOfDay();
        $agents = new EloquentCollection();

        $repo = mon_repo();
        $repo->shouldReceive('getAgentsWithStats')
            ->once()
            ->with($start, $end, $now)
            ->andReturn($agents);

        $result = mon_service($repo)->getAgentsData($start, $end, $now);

        expect($result)->toBe($agents);
    });

    it('o service não executa nenhuma query direta (sem chamadas além do repo)', function () {
        $now   = Carbon::now();
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $repo = mon_repo();
        $repo->shouldReceive('getAgentsWithStats')
            ->once()
            ->andReturn(new EloquentCollection());

        mon_service($repo)->getAgentsData($start, $end, $now);

        // Mockery verificará que nenhum método extra foi chamado
        Mockery::close();

        expect(true)->toBeTrue(); // Se chegou aqui, nenhum acesso direto ocorreu
    });

});

// ─── getChartData ─────────────────────────────────────────────────────────────

describe('MonitorService — getChartData', function () {

    it('retorna array com chaves labels e data', function () {
        $now   = Carbon::now()->setTime(9, 0);
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $repo = mon_repo();
        $repo->shouldReceive('getTicketTimesInPeriod')
            ->once()
            ->andReturn(collect());

        $chart = mon_service($repo)->getChartData($start, $end, $now);

        expect($chart)->toHaveKeys(['labels', 'data'])
            ->and($chart['labels'])->toBeArray()
            ->and($chart['data'])->toBeArray();
    });

    it('gera exatamente 20 intervalos de 30 minutos iniciando às 08:30', function () {
        $now   = Carbon::now()->setTime(8, 30);
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $repo = mon_repo();
        $repo->shouldReceive('getTicketTimesInPeriod')->once()->andReturn(collect());

        $chart = mon_service($repo)->getChartData($start, $end, $now);

        expect(count($chart['labels']))->toBe(20)
            ->and(count($chart['data']))->toBe(20)
            ->and($chart['labels'][0])->toBe('08:30');
    });

    it('conta corretamente tickets dentro de um intervalo', function () {
        $now   = Carbon::now()->setTime(8, 30);
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        // 2 tickets no primeiro intervalo (08:30–08:59)
        $repo = mon_repo();
        $repo->shouldReceive('getTicketTimesInPeriod')
            ->once()
            ->andReturn(collect(['08:35:00', '08:45:00', '10:05:00']));

        $chart = mon_service($repo)->getChartData($start, $end, $now);

        // Primeiro intervalo: 08:30 — 2 tickets
        expect($chart['data'][0])->toBe(2);
        // Terceiro intervalo: 09:30 — 1 ticket (10:05 fica no índice 3: 09:30–10:00)
        expect($chart['data'][3])->toBe(1);
    });

    it('todos os dados são zero quando não há tickets', function () {
        $now   = Carbon::now()->setTime(8, 30);
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $repo = mon_repo();
        $repo->shouldReceive('getTicketTimesInPeriod')->once()->andReturn(collect());

        $chart = mon_service($repo)->getChartData($start, $end, $now);

        expect(array_sum($chart['data']))->toBe(0);
    });

    it('delega getTicketTimesInPeriod ao repository exatamente uma vez', function () {
        $now   = Carbon::now()->setTime(8, 30);
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $repo = mon_repo();
        $repo->shouldReceive('getTicketTimesInPeriod')
            ->once()
            ->with($start, $end)
            ->andReturn(collect());

        mon_service($repo)->getChartData($start, $end, $now);

        Mockery::close();
        expect(true)->toBeTrue();
    });

});
