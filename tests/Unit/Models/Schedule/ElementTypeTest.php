<?php

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

describe('ElementType — fillable', function () {

    it('aceita name, label, type, module_id e group_id', function () {
        expect((new ElementType())->getFillable())
            ->toContain('name', 'label', 'type', 'module_id', 'group_id');
    });

});

describe('ElementType — timestamps', function () {

    it('timestamps estão desativados', function () {
        expect((new ElementType())->timestamps)->toBeFalse();
    });

});

describe('ElementType — tabela', function () {

    it('aponta para schedule_record_element_type', function () {
        expect((new ElementType())->getTable())->toBe('schedule_record_element_type');
    });

});

describe('ElementType — relacionamentos', function () {

    it('group() retorna instância de BelongsTo', function () {
        expect((new ElementType())->group())
            ->toBeInstanceOf(BelongsTo::class);
    });

    it('group() usa group_id como chave estrangeira', function () {
        expect((new ElementType())->group()->getForeignKeyName())->toBe('group_id');
    });

    it('group() aponta para ElementGroup', function () {
        $related = (new ElementType())->group()->getRelated();
        expect($related)->toBeInstanceOf(ElementGroup::class);
    });

});

describe('ElementType — integração com ElementGroup', function () {

    it('eager load de group retorna o grupo correto', function () {
        $group = \App\Models\Schedule\ElementGroup::firstOrCreate(['name' => 'Geral']);
        $module = \App\Models\Schedule\Module::firstOrCreate(['name' => 'Vendas RC'], ['project' => 'EasyMaster']);

        $type = ElementType::firstOrCreate(
            ['name' => 'et_test_group_load'],
            ['label' => 'Teste', 'type' => 'checkbox', 'module_id' => $module->id, 'group_id' => $group->id]
        );

        $loaded = ElementType::with('group')->find($type->id);

        expect($loaded->group)->not->toBeNull()
            ->and($loaded->group->name)->toBe('Geral');
    });

});
