<?php

use App\Models\CompanyModuleType;
use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ec_module(string $name = 'Módulo EC', string $project = 'EasyMaster'): Module
{
    return Module::firstOrCreate(['name' => $name], ['project' => $project]);
}

function ec_group(string $name = 'Grupo EC'): ElementGroup
{
    return ElementGroup::firstOrCreate(['name' => $name]);
}

function ec_element(string $name, string $label, Module $module, ElementGroup $group): ElementType
{
    return ElementType::firstOrCreate(
        ['name' => $name],
        [
            'label' => $label,
            'type' => 'checkbox',
            'module_id' => $module->id,
            'group_id' => $group->id,
        ]
    );
}

// ─── Autenticação ─────────────────────────────────────────────────────────────

describe('ElementController — autenticação', function () {

    it('retorna 401 para requisição sem autenticação', function () {
        $this->getJson(route('api.records.elements.index'))
            ->assertUnauthorized();
    });

    it('retorna 200 para agente autenticado', function () {
        actingAsAgent();

        $this->getJson(route('api.records.elements.index'))
            ->assertOk();
    });

});

// ─── index() — estrutura da resposta ──────────────────────────────────────────

describe('ElementController — index()', function () {

    it('resposta é um objeto com chaves dinâmicas (nomes dos grupos)', function () {
        actingAsAgent();
        $mod = ec_module('Mod Struct');
        $group = ec_group('Grupo Struct');
        ec_element('elem_struct', 'Struct', $mod, $group);

        $json = $this->getJson(route('api.records.elements.index'))
            ->assertOk()
            ->json();

        expect($json)->toBeArray()
            ->and(array_key_exists('Grupo Struct', $json))->toBeTrue();
    });

    it('cada elemento retorna id, name, label, type, module_id, group_id, group_name', function () {
        actingAsAgent();
        $mod = ec_module('Mod Fields');
        $group = ec_group('Grupo Fields');
        ec_element('elem_fields', 'Fields Label', $mod, $group);

        $json = $this->getJson(route('api.records.elements.index'))
            ->assertOk()
            ->json();

        $elem = collect($json['Grupo Fields'])->firstWhere('name', 'elem_fields');

        expect($elem)->not->toBeNull()
            ->and($elem)->toHaveKeys(['id', 'name', 'label', 'type', 'module_id', 'group_id', 'group_name'])
            ->and($elem['label'])->toBe('Fields Label')
            ->and($elem['type'])->toBe('checkbox')
            ->and($elem['group_name'])->toBe('Grupo Fields');
    });

    it('filtra por module_id passado como query param', function () {
        actingAsAgent();
        $modAlvo = ec_module('Mod Alvo EC');
        $modOutro = ec_module('Mod Outro EC');
        $group = ec_group('Grupo Filtro EC');

        ec_element('ec_alvo', 'Alvo', $modAlvo, $group);
        ec_element('ec_outro', 'Outro', $modOutro, $group);

        $json = $this->getJson(route('api.records.elements.index').'?module_id='.$modAlvo->id)
            ->assertOk()
            ->json();

        $allNames = collect($json)->flatten(1)->pluck('name');
        expect($allNames)->toContain('ec_alvo')
            ->and($allNames)->not->toContain('ec_outro');
    });

    it('aceita company_module_types.id quando o contexto é ticket', function () {
        actingAsAgent();
        $ratAlvo = ec_module('RAT Ticket Alvo');
        $ratOutro = ec_module('RAT Ticket Outro');
        $group = ec_group('Grupo Ticket EC');

        ec_element('ec_ticket_alvo', 'Alvo Ticket', $ratAlvo, $group);
        ec_element('ec_ticket_outro', 'Outro Ticket', $ratOutro, $group);

        $companyModule = CompanyModuleType::factory()->withRatModule($ratAlvo)->create();

        $json = $this->getJson(route('api.records.elements.index').'?module_id='.$companyModule->id.'&module_context=ticket')
            ->assertOk()
            ->json();

        $allNames = collect($json)->flatten(1)->pluck('name');
        expect($allNames)->toContain('ec_ticket_alvo')
            ->and($allNames)->not->toContain('ec_ticket_outro');
    });

    it('retorna objeto vazio quando module_id não tem elementos', function () {
        actingAsAgent();

        $json = $this->getJson(route('api.records.elements.index').'?module_id=999999')
            ->assertOk()
            ->json();

        expect($json)->toBeEmpty();
    });

});
