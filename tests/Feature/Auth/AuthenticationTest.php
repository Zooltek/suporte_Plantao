<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

const LOGIN_ROUTE = '/login';

test('a tela de login pode ser renderizada', function () {
    $response = $this->get(LOGIN_ROUTE);

    $response->assertStatus(200);
});

test('usuários podem autenticar usando a tela de login', function () {
    $user = User::factory()->create();

    $response = $this->post(LOGIN_ROUTE, [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user, 'web');
    $this->assertGuest('admin');
    $response->assertRedirect(route('portal.home'));
});

test('usuários não podem autenticar com senha inválida', function () {
    $user = User::factory()->create();

    $this->post(LOGIN_ROUTE, [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('usuários podem fazer logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest('web');
    $this->assertGuest('admin');
    $response->assertRedirect('/');
});

test('usuário consegue logar', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
        '_token' => csrf_token(),
    ])->assertRedirect();
});
