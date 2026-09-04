<?php

use App\Models\Tasks\Task;
use App\Models\User;

describe('Inbox de tarefas — atualização de status', function () {
    it('staff atualiza o status da tarefa pela inbox compartilhada', function () {
        $agent = actingAsAgent();
        $task = Task::factory()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'status' => 'pen',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.status.update', $task), [
                'status' => 'pro',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success', 'Status da tarefa atualizado com sucesso.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pro',
        ]);

        expect($task->fresh()->started_at)->not->toBeNull();
    });

    it('mantém a tarefa inalterada quando o usuário não pode modificá-la', function () {
        $owner = User::factory()->agent()->create();
        actingAsAgent();

        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'author_id' => $owner->id,
            'status' => 'pen',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.status.update', $task), [
                'status' => 'pro',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('warning', 'Você não tem permissão para alterar o status desta tarefa.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pen',
        ]);
    });

    it('rejeita status inválido com erro de validação', function () {
        $agent = actingAsAgent();
        $task = Task::factory()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'status' => 'pen',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.status.update', $task), [
                'status' => 'foo',
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHasErrors('status');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'pen',
        ]);
    });
});
