<?php

use App\Models\Schedule\Element;
use App\Models\Schedule\ElementType;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

describe('Element — fillable', function () {

    it('aceita element_id, record_id e value', function () {
        expect((new Element())->getFillable())
            ->toContain('element_id', 'record_id', 'value');
    });

});

describe('Element — timestamps', function () {

    it('timestamps estão desativados', function () {
        expect((new Element())->timestamps)->toBeFalse();
    });

});

describe('Element — tabela', function () {

    it('aponta para schedule_record_element', function () {
        expect((new Element())->getTable())->toBe('schedule_record_element');
    });

});

describe('Element — relacionamentos', function () {

    it('type() retorna instância de BelongsTo', function () {
        expect((new Element())->type())
            ->toBeInstanceOf(BelongsTo::class);
    });

    it('type() usa element_id como chave estrangeira', function () {
        expect((new Element())->type()->getForeignKeyName())->toBe('element_id');
    });

    it('type() aponta para ElementType', function () {
        $related = (new Element())->type()->getRelated();
        expect($related)->toBeInstanceOf(ElementType::class);
    });

});
