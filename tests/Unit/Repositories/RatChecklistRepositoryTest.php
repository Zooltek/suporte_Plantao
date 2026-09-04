<?php

/**
 * Testes de integração do RatChecklistRepository — SQLite in-memory.
 *
 * Cobrem todos os métodos públicos da implementação, validando queries
 * sem acionar lógica de negócio do Service.
 */

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Module;
use App\Repositories\RatChecklistRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function rcr_repo(): RatChecklistRepository
{
    return new RatChecklistRepository();
}

function rcr_module(array $attrs = []): Module
{
    return Module::factory()->create($attrs);
}

function rcr_group(array $attrs = []): ElementGroup
{
    return ElementGroup::factory()->create($attrs);
}

function rcr_item(array $attrs = []): ElementType
{
    return ElementType::factory()->create($attrs);
}

// ─── getModulesWithItems ───────────────────────────────────────────────────────

describe('RatChecklistRepository — getModulesWithItems', function () {

    it('retorna módulos com elementTypes e grupo carregados', function () {
        $module = rcr_module();
        $group  = rcr_group();
        $item   = rcr_item(['module_id' => $module->id, 'group_id' => $group->id]);

        $result = rcr_repo()->getModulesWithItems();

        $found = $result->firstWhere('id', $module->id);
        expect($found)->not->toBeNull();
        expect($found->relationLoaded('elementTypes'))->toBeTrue();
    });

    it('retorna lista vazia quando não há módulos', function () {
        $result = rcr_repo()->getModulesWithItems();
        expect($result)->toBeEmpty();
    });

});

// ─── getAllGroups ──────────────────────────────────────────────────────────────

describe('RatChecklistRepository — getAllGroups', function () {

    it('retorna todos os grupos ordenados por nome', function () {
        $b = rcr_group(['name' => 'Beta']);
        $a = rcr_group(['name' => 'Alfa']);

        $result = rcr_repo()->getAllGroups();
        $names  = $result->pluck('name')->toArray();

        expect($names[0])->toBe('Alfa');
        expect($names[1])->toBe('Beta');
    });

});

// ─── getAllModules ─────────────────────────────────────────────────────────────

describe('RatChecklistRepository — getAllModules', function () {

    it('retorna todos os módulos', function () {
        $m1 = rcr_module();
        $m2 = rcr_module();

        $result = rcr_repo()->getAllModules();

        expect($result->pluck('id')->toArray())
            ->toContain($m1->id)
            ->toContain($m2->id);
    });

});

// ─── findGroup ────────────────────────────────────────────────────────────────

describe('RatChecklistRepository — findGroup', function () {

    it('retorna o grupo pelo id', function () {
        $group = rcr_group();

        $found = rcr_repo()->findGroup($group->id);

        expect($found->id)->toBe($group->id);
    });

    it('lança ModelNotFoundException para id inexistente', function () {
        expect(fn () => rcr_repo()->findGroup(999999))
            ->toThrow(ModelNotFoundException::class);
    });

});

// ─── firstOrCreateGroup ───────────────────────────────────────────────────────

describe('RatChecklistRepository — firstOrCreateGroup', function () {

    it('cria um grupo quando o nome não existe', function () {
        $group = rcr_repo()->firstOrCreateGroup('Novo Grupo');

        expect($group->id)->not->toBeNull();
        $this->assertDatabaseHas('schedule_record_element_group', ['name' => 'Novo Grupo']);
    });

    it('retorna o grupo existente sem duplicar', function () {
        $existing = rcr_group(['name' => 'Grupo Existente']);

        $found = rcr_repo()->firstOrCreateGroup('  Grupo Existente  ');

        expect($found->id)->toBe($existing->id);
        expect(ElementGroup::where('name', 'Grupo Existente')->count())->toBe(1);
    });

});

// ─── createItem ───────────────────────────────────────────────────────────────

describe('RatChecklistRepository — createItem', function () {

    it('persiste um novo item de checklist', function () {
        $module = rcr_module();
        $group  = rcr_group();

        $data = [
            'name'      => 'item_teste',
            'label'     => 'Item Teste',
            'type'      => 'checkbox',
            'module_id' => $module->id,
            'group_id'  => $group->id,
        ];

        $item = rcr_repo()->createItem($data);

        expect($item->id)->not->toBeNull();
        $this->assertDatabaseHas('schedule_record_element_type', ['id' => $item->id, 'name' => 'item_teste']);
    });

});

// ─── updateItem ───────────────────────────────────────────────────────────────

describe('RatChecklistRepository — updateItem', function () {

    it('atualiza o label do item e retorna instância fresca', function () {
        $item = rcr_item(['label' => 'Original']);

        $updated = rcr_repo()->updateItem($item, ['label' => 'Atualizado']);

        expect($updated->label)->toBe('Atualizado');
        $this->assertDatabaseHas('schedule_record_element_type', ['id' => $item->id, 'label' => 'Atualizado']);
    });

});

// ─── deleteItem ───────────────────────────────────────────────────────────────

describe('RatChecklistRepository — deleteItem', function () {

    it('remove o item do banco', function () {
        $item = rcr_item();

        rcr_repo()->deleteItem($item);

        $this->assertDatabaseMissing('schedule_record_element_type', ['id' => $item->id]);
    });

});

// ─── itemHasElements ──────────────────────────────────────────────────────────

describe('RatChecklistRepository — itemHasElements', function () {

    it('retorna false quando o item não possui elements vinculados', function () {
        $item = rcr_item();

        $result = rcr_repo()->itemHasElements($item);

        expect($result)->toBeFalse();
    });

});
