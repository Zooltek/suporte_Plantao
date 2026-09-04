<?php

use App\Models\Tasks\Task;
use App\Models\User;

/**
 * Valida que TaskController::updateStatus() rejeita status inválidos
 * e aceita apenas os valores permitidos após a correção de segurança.
 *
 * Cobre a adição de $request->validate() em TaskController::updateStatus().
 */
describe('TaskController — updateStatus — validação de status', function () {

    beforeEach(function () {
        // Bypassa todos os middlewares — testamos apenas a camada de validação
        $this->withoutMiddleware();

        $this->agent = User::factory()->agent()->create();
        $this->task  = Task::factory()->create([
            'user_id'   => $this->agent->id,
            'author_id' => $this->agent->id,
            'status'    => 'pen',
        ]);

        // Autentica o agente para que canModify() não bloqueie nos testes de happy path
        $this->actingAs($this->agent, 'admin');
    });

    // ── valores inválidos (validação rejeita antes de chegar ao service) ───────

    it('status vazio retorna 422', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => '',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('status');
    });

    it('status não permitido retorna 422', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => 'invalid_value',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('status');
    });

    it('status ausente retorna 422', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('status');
    });

    it('injeção de status arbitrário (ex: "admin") retorna 422', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => 'admin',
        ])->assertStatus(422)
          ->assertJsonValidationErrors('status');
    });

    // ── valores válidos ───────────────────────────────────────────────────────

    it('status "pro" (em progresso) é aceito e salvo', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => 'pro',
        ])->assertOk()
          ->assertJson(['status' => true]);

        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'status' => 'pro']);
    });

    it('status "don" (concluído) é aceito', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => 'don',
        ])->assertOk();

        $this->assertDatabaseHas('tasks', ['id' => $this->task->id, 'status' => 'don']);
    });

    it('status "can" (cancelado) é aceito', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status' => 'can',
        ])->assertOk();
    });

    it('description é opcional e aceito quando presente', function () {
        $this->putJson("/api/v1/tasks/tasks/{$this->task->id}/status", [
            'status'      => 'pro',
            'description' => 'Trabalhando nisto agora',
        ])->assertOk();
    });

});
