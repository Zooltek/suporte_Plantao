<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Fluxo 1 — Login staff canônico.
 *
 * Valida a entrada via /admin/login, o uso do guard admin e o acesso
 * ao painel do agente no fluxo web atual.
 */
describe('Fluxo 1 — Login staff via /admin/login', function () {

    it('tela canônica de login staff exibe o contexto atual da interface', function () {
        $this->get(route('admin.login'))
            ->assertOk()
            ->assertSee('Acesso Restrito')
            ->assertSee('Acessar Conta');
    });

    it('agente ativo realiza login com credenciais válidas pela rota canônica', function () {
        // Arrange
        $agent = User::factory()->agent()->create([
            'password' => Hash::make('senha@123'),
            'active'   => true,
        ]);

        // Act
        $response = $this->post(route('admin.login'), [
            'email'    => $agent->email,
            'password' => 'senha@123',
        ]);

        // Assert — redireciona e autentica no guard admin
        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($agent, 'admin');
    });

    it('senha incorreta rejeita autenticação', function () {
        // Arrange
        $agent = User::factory()->agent()->create([
            'password' => Hash::make('correta'),
            'active'   => true,
        ]);

        // Act
        $response = $this->post(route('admin.login'), [
            'email'    => $agent->email,
            'password' => 'errada',
        ]);

        // Assert
        $response->assertSessionHasErrors('email');
        $this->assertGuest('admin');
    });

    it('conta inativa é bloqueada no login', function () {
        // Arrange
        $agent = User::factory()->agent()->create([
            'password' => Hash::make('senha@123'),
            'active'   => false,
        ]);

        // Act
        $response = $this->post(route('admin.login'), [
            'email'    => $agent->email,
            'password' => 'senha@123',
        ]);

        // Assert
        $response->assertSessionHasErrors();
        $this->assertGuest('admin');
    });

    it('agente autenticado acessa o painel web sem ser redirecionado ao login', function () {
        actingAsAgent();

        // Act & Assert
        $this->get(route('agent.index'))->assertOk();
    });

    it('visitante não autenticado é redirecionado ao tentar acessar o painel', function () {
        $this->get(route('agent.index'))->assertRedirect();
    });

});
