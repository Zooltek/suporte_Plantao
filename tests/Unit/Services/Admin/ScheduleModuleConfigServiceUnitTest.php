<?php

/**
 * Testes unitários do ScheduleModuleConfigService.
 *
 * Todas as chamadas ao banco são interceptadas pelo Mock do
 * ScheduleModuleConfigRepositoryInterface, isolando a regra de negócio do Service.
 */

use App\Contracts\Repositories\ScheduleModuleConfigRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule\Module;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;
use Illuminate\Database\Eloquent\Collection;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function smcs_service(ScheduleModuleConfigRepositoryInterface $repo): ScheduleModuleConfigService
{
    return new ScheduleModuleConfigService($repo);
}

function smcs_makeCompany(): Company
{
    $c = new Company(['name' => 'Empresa Teste', 'cnpj' => '00.000.000/0001-00']);
    $c->id = 1;

    return $c;
}

function smcs_makeModule(array $attrs = []): Module
{
    $m = new Module(array_merge(['name' => 'Módulo A', 'project' => 'Fiscal'], $attrs));
    $m->id = $attrs['id'] ?? 1;

    return $m;
}

// ─── getForCompany — com módulos associados ───────────────────────────────────

describe('ScheduleModuleConfigService — getForCompany com módulos associados', function () {

    it('retorna os módulos associados quando a coleção não está vazia', function () {
        $repo    = Mockery::mock(ScheduleModuleConfigRepositoryInterface::class);
        $company = smcs_makeCompany();
        $col     = new Collection([smcs_makeModule()]);

        $repo->shouldReceive('getAssignedModules')->once()->with($company)->andReturn($col);

        $result = smcs_service($repo)->getForCompany($company);

        expect($result)->toBe($col);
    });

});

// ─── getForCompany — fallback quando nenhum módulo associado ──────────────────

describe('ScheduleModuleConfigService — getForCompany com fallback', function () {

    it('retorna todos os módulos quando nenhum está associado', function () {
        $repo    = Mockery::mock(ScheduleModuleConfigRepositoryInterface::class);
        $company = smcs_makeCompany();
        $all     = new Collection([smcs_makeModule(), smcs_makeModule(['id' => 2])]);

        $repo->shouldReceive('getAssignedModules')->once()->with($company)->andReturn(new Collection());
        $repo->shouldReceive('getAllModules')->once()->andReturn($all);

        $result = smcs_service($repo)->getForCompany($company);

        expect($result)->toBe($all);
    });

});

// ─── syncForCompany ───────────────────────────────────────────────────────────

describe('ScheduleModuleConfigService — syncForCompany', function () {

    it('delega ao repositório passando company e array de ids', function () {
        $repo    = Mockery::mock(ScheduleModuleConfigRepositoryInterface::class);
        $company = smcs_makeCompany();
        $ids     = [1, 3, 7];

        $repo->shouldReceive('syncModules')->once()->with($company, $ids)->andReturnNull();

        smcs_service($repo)->syncForCompany($company, $ids);

        expect(true)->toBeTrue();
    });

});

// ─── getAllGrouped ─────────────────────────────────────────────────────────────

describe('ScheduleModuleConfigService — getAllGrouped', function () {

    it('agrupa os módulos pelo campo project usando lógica do Service', function () {
        $repo   = Mockery::mock(ScheduleModuleConfigRepositoryInterface::class);
        $fiscal = smcs_makeModule(['id' => 1, 'project' => 'Fiscal']);
        $logist = smcs_makeModule(['id' => 2, 'project' => 'Logística']);
        $nullPj = smcs_makeModule(['id' => 3, 'project' => null]);

        $col = new Collection([$fiscal, $logist, $nullPj]);
        $repo->shouldReceive('getAllModules')->once()->andReturn($col);

        $grouped = smcs_service($repo)->getAllGrouped();

        expect($grouped->keys()->toArray())->toContain('Fiscal', 'Logística', 'Geral');
    });

});
