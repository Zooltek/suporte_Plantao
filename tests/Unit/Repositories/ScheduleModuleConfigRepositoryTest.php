<?php

/**
 * Testes de integração do ScheduleModuleConfigRepository — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando que as queries
 * produzem os resultados esperados sem acionar lógica de negócio do Service.
 */

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Repositories\ScheduleModuleConfigRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function smcr_repo(): ScheduleModuleConfigRepository
{
    return new ScheduleModuleConfigRepository();
}

function smcr_company(): Company
{
    return Company::factory()->create();
}

function smcr_module(array $attrs = []): Module
{
    return Module::factory()->create($attrs);
}

// ─── getAssignedModules ────────────────────────────────────────────────────────

describe('ScheduleModuleConfigRepository — getAssignedModules', function () {

    it('retorna os módulos associados ao cliente', function () {
        $company = smcr_company();
        $module  = smcr_module();
        $company->scheduleModules()->attach($module->id);

        $result = smcr_repo()->getAssignedModules($company);

        expect($result->pluck('id')->toArray())->toContain($module->id);
    });

    it('retorna coleção vazia quando nenhum módulo está associado', function () {
        $company = smcr_company();

        $result = smcr_repo()->getAssignedModules($company);

        expect($result)->toBeEmpty();
    });

});

// ─── getAllModules ─────────────────────────────────────────────────────────────

describe('ScheduleModuleConfigRepository — getAllModules', function () {

    it('retorna todos os módulos cadastrados', function () {
        $m1 = smcr_module();
        $m2 = smcr_module();

        $result = smcr_repo()->getAllModules();

        expect($result->pluck('id')->toArray())
            ->toContain($m1->id)
            ->toContain($m2->id);
    });

    it('retorna lista vazia quando não há módulos', function () {
        $result = smcr_repo()->getAllModules();
        expect($result)->toBeEmpty();
    });

});

// ─── syncModules ──────────────────────────────────────────────────────────────

describe('ScheduleModuleConfigRepository — syncModules', function () {

    it('associa os módulos informados ao cliente', function () {
        $company = smcr_company();
        $m1      = smcr_module();
        $m2      = smcr_module();

        smcr_repo()->syncModules($company, [$m1->id, $m2->id]);

        $assigned = $company->scheduleModules()->pluck('schedule_record_module.id')->toArray();
        expect($assigned)->toContain($m1->id)->toContain($m2->id);
    });

    it('substitui integralmente a seleção anterior', function () {
        $company = smcr_company();
        $old     = smcr_module();
        $new     = smcr_module();
        $company->scheduleModules()->attach($old->id);

        smcr_repo()->syncModules($company, [$new->id]);

        $assigned = $company->scheduleModules()->pluck('schedule_record_module.id')->toArray();
        expect($assigned)->toContain($new->id);
        expect($assigned)->not->toContain($old->id);
    });

    it('limpa todas as associações quando array vazio é fornecido', function () {
        $company = smcr_company();
        $module  = smcr_module();
        $company->scheduleModules()->attach($module->id);

        smcr_repo()->syncModules($company, []);

        expect($company->scheduleModules()->count())->toBe(0);
    });

});
