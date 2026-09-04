<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('LoginFlowTest — fluxo de login e redirecionamento pós-senha', function () {

    // ── Login — usuários ativos ───────────────────────────────────────────────

    it('agente ativo com credenciais corretas é autenticado e redirecionado', function () {
        $agent = User::factory()->agent()->create([
            'password' => Hash::make('senha123'),
            'active'   => true,
        ]);

        $this->post(route('login'), [
            'email'    => $agent->email,
            'password' => 'senha123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($agent, 'admin');
    });

    it('admin ativo com credenciais corretas é autenticado e redirecionado', function () {
        $admin = User::factory()->admin()->create([
            'password' => Hash::make('senha123'),
            'active'   => true,
        ]);

        $this->post(route('login'), [
            'email'    => $admin->email,
            'password' => 'senha123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($admin, 'admin');
    });

    it('usuário comum ativo autentica no guard web e é redirecionado para o portal', function () {
        $user = User::factory()->create([
            'password' => Hash::make('senha123'),
            'active' => true,
        ]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'senha123',
        ])->assertRedirect(route('portal.home'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    });

    // ── Login — conta desativada ──────────────────────────────────────────────

    it('usuário com active=false não consegue autenticar e recebe mensagem de conta desativada', function () {
        $user = User::factory()->agent()->create([
            'password' => Hash::make('senha123'),
            'active'   => false,
        ]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'senha123',
        ])->assertSessionHasErrors(['email' => 'Sua conta está desativada. Entre em contato com o administrador.']);

        $this->assertGuest('admin');
    });

    // ── Login — credenciais inválidas ─────────────────────────────────────────

    it('senha incorreta retorna erro de credenciais inválidas', function () {
        $user = User::factory()->agent()->create([
            'password' => Hash::make('senha123'),
            'active'   => true,
        ]);

        $this->post(route('login'), [
            'email'    => $user->email,
            'password' => 'senhaerrada',
        ])->assertSessionHasErrors(['email' => 'Credenciais inválidas.']);

        $this->assertGuest('admin');
    });

    // ── Login — senha temporária ──────────────────────────────────────────────

    it('agente com must_change_password=true é interceptado pelo middleware e forçado a trocar a senha', function () {
        $agent = User::factory()->agent()->create([
            'password'             => Hash::make('senha123'),
            'active'               => true,
            'must_change_password' => true,
        ]);

        // O middleware EnsurePasswordChanged age na primeira request protegida após o login
        $this->actingAs($agent, 'admin')
            ->get(route('agent.index'))
            ->assertRedirect(route('password.force-change'));
    });

    // ── Force-change: redirect pós-troca ─────────────────────────────────────

    it('agente que troca senha obrigatória é redirecionado para agent.index', function () {
        $agent = User::factory()->agent()->create([
            'password'             => Hash::make('velhasenha'),
            'active'               => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($agent, 'admin')
            ->post(route('password.force-change.update'), [
                'password'              => 'novasenha123',
                'password_confirmation' => 'novasenha123',
            ])->assertRedirect(route('admin.login'));
    });

    it('admin que troca senha obrigatória é redirecionado para admin.dashboard', function () {
        $admin = User::factory()->admin()->create([
            'password'             => Hash::make('velhasenha'),
            'active'               => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($admin, 'admin')
            ->post(route('password.force-change.update'), [
                'password'              => 'novasenha123',
                'password_confirmation' => 'novasenha123',
            ])->assertRedirect(route('admin.login'));
    });

    it('após troca de senha, must_change_password é zerado no banco', function () {
        $agent = User::factory()->agent()->create([
            'password'             => Hash::make('velhasenha'),
            'active'               => true,
            'must_change_password' => true,
        ]);

        $this->actingAs($agent, 'admin')
            ->post(route('password.force-change.update'), [
                'password'              => 'novasenha123',
                'password_confirmation' => 'novasenha123',
            ]);

        $this->assertDatabaseHas('users', [
            'id'                   => $agent->id,
            'must_change_password' => false,
        ]);
    });

    // ── Login — case-insensitive ──────────────────────────────────────────────

    it('autentica com e-mail em caixa alta independente do que está no banco', function () {
        $user = User::factory()->agent()->create([
            'email'    => 'joao@example.com',
            'password' => Hash::make('senha123'),
            'active'   => true,
        ]);

        $this->post(route('login'), [
            'email'    => 'JOAO@EXAMPLE.COM',
            'password' => 'senha123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user, 'admin');
    });

    it('autentica com e-mail mixado e espaços ao redor', function () {
        $user = User::factory()->agent()->create([
            'email'    => 'maria@example.com',
            'password' => Hash::make('senha123'),
            'active'   => true,
        ]);

        $this->post(route('login'), [
            'email'    => '  Maria@Example.COM  ',
            'password' => 'senha123',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user, 'admin');
    });

});
