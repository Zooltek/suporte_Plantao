<?php

// ─────────────────────────────────────────────────────────────────────────────
// Integração Financeiro — autenticação por API key (OWASP A07/A04)
//
// Garante o contrato de segurança dos endpoints /api/v1/integration/*:
// exige X-API-Key, fail-closed quando a chave não está configurada e só passa
// pela auth (deixando de retornar 401) com a chave correta.
// ─────────────────────────────────────────────────────────────────────────────

beforeEach(function () {
    config(['services.financeiro.api_key' => 'test-integration-key']);
});

$store = '/api/v1/integration/customers';

describe('POST /api/v1/integration/customers — autenticação', function () use ($store) {

    it('retorna 401 quando o header X-API-Key está ausente (A07)', function () use ($store) {
        $this->postJson($store, [])
            ->assertStatus(401)
            ->assertJsonPath('message', 'Credencial de integração inválida ou ausente.');
    });

    it('retorna 401 quando a API key é inválida (A07)', function () use ($store) {
        $this->postJson($store, [], ['X-API-Key' => 'chave-errada'])
            ->assertStatus(401);
    });

    it('faz fail-closed: 401 mesmo com chave enviada quando o servidor não tem chave configurada (A04)', function () use ($store) {
        config(['services.financeiro.api_key' => '']);

        $this->postJson($store, [], ['X-API-Key' => 'qualquer-coisa'])
            ->assertStatus(401);
    });

    it('passa pela auth com a chave correta (201 ao criar)', function () use ($store) {
        $this->postJson($store, [
            'id' => 154,
            'name' => 'ACME',
            'cnpj' => '18587938000115',
            'business_group' => [
                'code' => 'GROUP01',
                'name' => 'Grupo ACME',
            ],
        ], [
            'X-API-Key' => 'test-integration-key',
        ])->assertStatus(201);
    });

});

describe('PATCH inactivate/reactivate — autenticação', function () {

    it('inactivate exige API key (401 sem chave)', function () {
        $this->patchJson('/api/v1/integration/customers/154/inactivate')
            ->assertStatus(401);
    });

    it('inactivate passa pela auth com a chave correta (404 quando id inexistente)', function () {
        $this->patchJson('/api/v1/integration/customers/999999/inactivate', [], [
            'X-API-Key' => 'test-integration-key',
        ])->assertStatus(404);
    });

    it('reactivate passa pela auth com a chave correta (404 quando id inexistente)', function () {
        $this->patchJson('/api/v1/integration/customers/999999/reactivate', [], [
            'X-API-Key' => 'test-integration-key',
        ])->assertStatus(404);
    });

    it('rejeita id não numérico na rota (404)', function () {
        $this->patchJson('/api/v1/integration/customers/abc/inactivate', [], [
            'X-API-Key' => 'test-integration-key',
        ])->assertStatus(404);
    });

});
