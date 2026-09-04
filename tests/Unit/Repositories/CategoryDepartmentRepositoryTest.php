<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Department;
use App\Repositories\CategoryRepository;

function crd_repo(): CategoryRepository
{
    return new CategoryRepository;
}

function crd_department(string $name = 'Comercial Test'): Department
{
    return Department::factory()->create(['name' => $name]);
}

describe('CategoryRepository — persistência de department_id', function () {

    it('grava department_id ao criar categoria via createCategory', function () {
        $department = crd_department('Comercial QA');

        $category = crd_repo()->createCategory(
            [
                'parent_id' => 0,
                'sort_order' => 0,
                'status' => 1,
                'visible' => 1,
                'ticket_category_id' => 1,
                'profile' => 0,
                'header' => 1,
                'priority' => 'low',
                'department_id' => $department->id,
            ],
            ['name' => 'Cat', 'description' => null, 'permalink' => 'cat-'.uniqid()],
        );

        $this->assertDatabaseHas('solutions_category', [
            'category_id' => $category->category_id,
            'department_id' => $department->id,
        ]);
    });

    it('atualiza department_id via updateCategory', function () {
        $deptA = crd_department('Suporte QA');
        $deptB = crd_department('Financeiro QA');

        $category = Category::factory()->create([
            'parent_id' => 0,
            'priority' => 'low',
            'department_id' => $deptA->id,
        ]);
        CategoryDescription::factory()->create(['category_id' => $category->category_id]);

        crd_repo()->updateCategory(
            $category->fresh(),
            ['priority' => 'low', 'department_id' => $deptB->id],
            null,
        );

        $this->assertDatabaseHas('solutions_category', [
            'category_id' => $category->category_id,
            'department_id' => $deptB->id,
        ]);
    });

    it('limpa department_id quando recebe null', function () {
        $department = crd_department('Para Limpar');

        $category = Category::factory()->create([
            'parent_id' => 0,
            'priority' => 'low',
            'department_id' => $department->id,
        ]);
        CategoryDescription::factory()->create(['category_id' => $category->category_id]);

        crd_repo()->updateCategory(
            $category->fresh(),
            ['priority' => 'low', 'department_id' => null],
            null,
        );

        $this->assertDatabaseHas('solutions_category', [
            'category_id' => $category->category_id,
            'department_id' => null,
        ]);
    });

    it('carrega a relação belongsTo Department', function () {
        $department = crd_department('Eager Test');

        $category = Category::factory()->create([
            'parent_id' => 0,
            'priority' => 'low',
            'department_id' => $department->id,
        ]);

        $loaded = Category::with('department')->find($category->category_id);

        expect($loaded->relationLoaded('department'))->toBeTrue();
        expect($loaded->department)->not->toBeNull();
        expect($loaded->department->id)->toBe($department->id);
        expect($loaded->department->name)->toBe('Eager Test');
    });

});
