<?php

/**
 * Testes unitários — ElementService::getGroupedElements()
 *
 * Contrato atual:
 *   - schedule: module_id já é schedule_record_module.id
 *   - ticket: module_id é company_module_types.id e é resolvido via rat_module_id
 *   - Módulo de ticket sem template RAT vinculado → retorna coleção vazia
 *   - Sem filtro (null) → retorna todos os elementos agrupados
 */

use App\Models\CompanyModuleType;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module as RatModule;
use App\Services\Agent\ElementService;
use Illuminate\Support\Collection;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function es_rat_module(string $name = 'RAT Módulo Teste'): RatModule
{
    return RatModule::firstOrCreate(['name' => $name], ['project' => 'EasyMaster']);
}

function es_company_module(RatModule $ratModule, string $name = 'Módulo Helpdesk'): CompanyModuleType
{
    static $counter = 0;
    $counter++;

    return CompanyModuleType::factory()->withRatModule($ratModule)->create([
        'name' => $name.' '.$counter,
    ]);
}

function es_group(string $name = 'Grupo Teste'): ElementGroup
{
    return ElementGroup::firstOrCreate(['name' => $name]);
}

function es_element(string $name, string $label, RatModule $ratModule, ?ElementGroup $group = null): ElementType
{
    return ElementType::firstOrCreate(
        ['name' => $name],
        [
            'label' => $label,
            'type' => 'checkbox',
            'module_id' => $ratModule->id,
            'group_id' => $group?->id,
        ]
    );
}

// ─── sem filtro ───────────────────────────────────────────────────────────────

describe('ElementService — getGroupedElements sem filtro', function () {

    it('retorna instância de Collection', function () {
        $result = app(ElementService::class)->getGroupedElements();

        expect($result)->toBeInstanceOf(Collection::class);
    });

    it('retorna todos os elementos agrupados pelo nome do grupo', function () {
        $ratA = es_rat_module('RAT A Sem Filtro');
        $ratB = es_rat_module('RAT B Sem Filtro');
        $groupX = es_group('Grupo X Sem Filtro');
        $groupY = es_group('Grupo Y Sem Filtro');

        es_element('elem_ax_sf', 'Elem AX', $ratA, $groupX);
        es_element('elem_by_sf', 'Elem BY', $ratB, $groupY);

        $result = app(ElementService::class)->getGroupedElements();

        expect($result->has('Grupo X Sem Filtro'))->toBeTrue()
            ->and($result->has('Grupo Y Sem Filtro'))->toBeTrue();
    });

    it('cada chave do resultado é o nome do grupo (string)', function () {
        $rat = es_rat_module('RAT Keys');
        $group = es_group('Grupo Keys');
        es_element('elem_keys', 'Keys', $rat, $group);

        $result = app(ElementService::class)->getGroupedElements();

        $result->keys()->each(fn ($key) => expect($key)->toBeString());
    });

});

// ─── com companyModuleTypeId ──────────────────────────────────────────────────

describe('ElementService — getGroupedElements com filtro', function () {

    it('filtra por schedule_record_module.id quando o contexto é schedule', function () {
        $ratAlvo = es_rat_module('RAT Schedule Alvo');
        $ratOutro = es_rat_module('RAT Schedule Outro');
        $group = es_group('Grupo Schedule');

        es_element('elem_schedule_alvo', 'Schedule Alvo', $ratAlvo, $group);
        es_element('elem_schedule_outro', 'Schedule Outro', $ratOutro, $group);

        $result = app(ElementService::class)->getGroupedElements($ratAlvo->id);

        $allNames = $result->flatten()->pluck('name');
        expect($allNames)->toContain('elem_schedule_alvo')
            ->and($allNames)->not->toContain('elem_schedule_outro');
    });

    it('filtra apenas elementos do módulo RAT vinculado ao CompanyModuleType informado no contexto ticket', function () {
        $ratAlvo = es_rat_module('RAT Alvo');
        $ratOutro = es_rat_module('RAT Outro');
        $group = es_group('Grupo Filtro');

        es_element('elem_alvo_filtro', 'Alvo', $ratAlvo, $group);
        es_element('elem_outro_filtro', 'Outro', $ratOutro, $group);

        $companyModule = es_company_module($ratAlvo, 'CM Alvo');

        $result = app(ElementService::class)->getGroupedElements($companyModule->id, ElementService::CONTEXT_TICKET);

        $allNames = $result->flatten()->pluck('name');
        expect($allNames)->toContain('elem_alvo_filtro')
            ->and($allNames)->not->toContain('elem_outro_filtro');
    });

    it('retorna Collection vazia quando CompanyModuleType não tem rat_module_id vinculado no contexto ticket', function () {
        $companyModule = CompanyModuleType::factory()->create(['rat_module_id' => null]);

        $result = app(ElementService::class)->getGroupedElements($companyModule->id, ElementService::CONTEXT_TICKET);

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toBeEmpty();
    });

    it('retorna Collection vazia quando companyModuleTypeId não existe no contexto ticket', function () {
        $result = app(ElementService::class)->getGroupedElements(999999, ElementService::CONTEXT_TICKET);

        expect($result)->toBeInstanceOf(Collection::class)
            ->and($result)->toBeEmpty();
    });

    it('elementos dentro de cada grupo estão ordenados por name (ASC)', function () {
        $rat = es_rat_module('RAT Ordem');
        $group = es_group('Grupo Ordem');

        es_element('zzz_last_ord', 'Last', $rat, $group);
        es_element('aaa_first_ord', 'First', $rat, $group);
        es_element('mmm_mid_ord', 'Mid', $rat, $group);

        $companyModule = es_company_module($rat, 'CM Ordem');

        $result = app(ElementService::class)->getGroupedElements($companyModule->id, ElementService::CONTEXT_TICKET);

        $names = $result['Grupo Ordem']->pluck('name')->toArray();
        $sorted = $names;
        sort($sorted);
        expect($names)->toBe($sorted);
    });

    it('dois CompanyModuleTypes com o mesmo rat_module_id retornam os mesmos itens RAT', function () {
        $rat = es_rat_module('RAT Compartilhado');
        $group = es_group('Grupo Compartilhado');
        es_element('elem_compartilhado', 'Compartilhado', $rat, $group);

        $cm1 = es_company_module($rat, 'CM Compartilhado 1');
        $cm2 = es_company_module($rat, 'CM Compartilhado 2');

        $result1 = app(ElementService::class)->getGroupedElements($cm1->id, ElementService::CONTEXT_TICKET);
        $result2 = app(ElementService::class)->getGroupedElements($cm2->id, ElementService::CONTEXT_TICKET);

        expect($result1->flatten()->pluck('name')->toArray())
            ->toBe($result2->flatten()->pluck('name')->toArray());
    });

});
