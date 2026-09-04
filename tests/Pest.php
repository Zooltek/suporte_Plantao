<?php

uses(
    Tests\DuskTestCase::class,
    // Illuminate\Foundation\Testing\DatabaseMigrations::class,
)->in('Browser');

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class)->in('Feature', 'Unit');

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

expect()->extend('toBeValidHexColor', function () {
    return $this->toMatch('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/');
});

function actingAsAdmin(array $attributes = []): User
{
    $user = User::factory()->admin()->create($attributes);
    test()->actingAs($user, 'admin');
    return $user;
}

function actingAsAgent(array $attributes = []): User
{
    $user = User::factory()->agent()->create($attributes);
    test()->actingAs($user, 'admin');
    return $user;
}

function actingAsUser(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    test()->actingAs($user);
    return $user;
}
