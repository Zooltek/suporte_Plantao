<?php

use App\Models\Schedule\ElementGroup;
use App\Models\Schedule\ElementType;
use Illuminate\Database\Eloquent\Relations\HasMany;

describe('ElementGroup — fillable', function () {

    it('aceita name', function () {
        expect((new ElementGroup())->getFillable())
            ->toContain('name');
    });

});

describe('ElementGroup — timestamps', function () {

    it('timestamps estão desativados', function () {
        expect((new ElementGroup())->timestamps)->toBeFalse();
    });

});

describe('ElementGroup — tabela', function () {

    it('aponta para schedule_record_element_group', function () {
        expect((new ElementGroup())->getTable())->toBe('schedule_record_element_group');
    });

});

describe('ElementGroup — relacionamentos', function () {

    it('elementTypes() retorna instância de HasMany', function () {
        expect((new ElementGroup())->elementTypes())
            ->toBeInstanceOf(HasMany::class);
    });

    it('elementTypes() aponta para ElementType', function () {
        $related = (new ElementGroup())->elementTypes()->getRelated();
        expect($related)->toBeInstanceOf(ElementType::class);
    });

});

describe('ElementGroup — persistência', function () {

    it('firstOrCreate cria e recupera pelo name', function () {
        $a = ElementGroup::firstOrCreate(['name' => 'eg_test_persist']);
        $b = ElementGroup::firstOrCreate(['name' => 'eg_test_persist']);

        expect($a->id)->toBe($b->id)
            ->and(ElementGroup::where('name', 'eg_test_persist')->count())->toBe(1);
    });

});
