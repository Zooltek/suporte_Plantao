<?php

use App\Models\Category;

describe('CategoryFactory', function () {

    it('state withDescription cria nome humano para filtros', function () {
        $category = Category::factory()
            ->withDescription('Financeiro')
            ->create(['parent_id' => 0]);

        $category->refresh()->load('description');

        expect($category->display_name)->toBe('Financeiro')
            ->and($category->description?->name)->toBe('Financeiro');
    });
});
