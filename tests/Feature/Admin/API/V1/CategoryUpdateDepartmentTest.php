<?php

use App\Models\Category;
use App\Models\CategoryDescription;
use App\Models\Department;
use App\Models\User;

it('PUT /admin/api/v1/categories/{id} grava department_id enviado', function () {
    $admin = User::factory()->admin()->create();
    $dept = Department::factory()->create(['name' => 'Setor Alvo Update']);

    $cat = Category::factory()->create([
        'parent_id' => 0,
        'priority' => 'low',
        'department_id' => null,
    ]);
    CategoryDescription::factory()->create([
        'category_id' => $cat->category_id,
        'name' => 'Categoria Original',
        'permalink' => 'cat-original-'.$cat->category_id,
    ]);

    $payload = [
        'name' => 'Categoria Original',
        'parent_id' => 0,
        'priority' => 'high',
        'permalink' => 'cat-original-'.$cat->category_id,
        'description' => 'desc qualquer',
        'department_id' => $dept->id,
    ];

    $this->actingAs($admin, 'admin')
        ->putJson("/admin/api/v1/categories/{$cat->category_id}", $payload)
        ->assertOk();

    expect($cat->fresh()->department_id)->toBe($dept->id);
});

it('PUT permite limpar department_id enviando null', function () {
    $admin = User::factory()->admin()->create();
    $dept = Department::factory()->create(['name' => 'Para Limpar']);

    $cat = Category::factory()->create([
        'parent_id' => 0,
        'priority' => 'low',
        'department_id' => $dept->id,
    ]);
    CategoryDescription::factory()->create([
        'category_id' => $cat->category_id,
        'name' => 'Cat com dept',
        'permalink' => 'cat-comdept-'.$cat->category_id,
    ]);

    $this->actingAs($admin, 'admin')
        ->putJson("/admin/api/v1/categories/{$cat->category_id}", [
            'name' => 'Cat com dept',
            'parent_id' => 0,
            'priority' => 'low',
            'permalink' => 'cat-comdept-'.$cat->category_id,
            'description' => '',
            'department_id' => null,
        ])
        ->assertOk();

    expect($cat->fresh()->department_id)->toBeNull();
});
