<?php

use App\Models\Company;
use App\Models\Tasks\Task;
use App\Models\User;

describe('Inbox de tarefas — edição inline', function () {
    it('card da tarefa expõe ações de visualizar e editar', function () {
        $admin = actingAsAdmin();

        Task::factory()->create([
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
            'title' => 'Tarefa com ações visíveis',
            'content' => 'Detalhes para expandir a visualização.',
        ]);

        $response = $this->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Visualizar')
            ->assertSee('Editar');

        expect($response->viewData('tasks')->pluck('title')->all())
            ->toContain('Tarefa com ações visíveis');
    });

    it('staff atualiza os dados principais da tarefa pela edição inline', function () {
        $admin = actingAsAdmin();
        $customer = Company::factory()->create();

        $task = Task::factory()->create([
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
            'title' => 'Título original',
            'content' => 'Descrição original',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.update', $task), [
                'title' => 'Título atualizado',
                'content' => 'Descrição atualizada via inbox',
                'user_id' => $admin->id,
                'customer_id' => $customer->id,
                'classification' => 'fix',
                'delivery_at' => '2026-04-12',
                'status' => 'pro',
                'editing_task_id' => $task->id,
            ])
            ->assertRedirect(route('tasks.index', ['task' => $task->id]))
            ->assertSessionHas('success', 'Tarefa atualizada com sucesso.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Título atualizado',
            'content' => 'Descrição atualizada via inbox',
            'customer_id' => $customer->id,
            'classification' => 'fix',
            'status' => 'pro',
        ]);

        expect($task->fresh()->delivery_at?->format('Y-m-d'))->toBe('2026-04-12');

        $this->get(route('tasks.index', ['task' => $task->id]))->assertOk();
    });

    it('aceita delivery_at no formato brasileiro d/m/Y na edição inline', function () {
        $admin = actingAsAdmin();

        $task = Task::factory()->create([
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
            'title' => 'Título original',
            'content' => 'Descrição original',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.update', $task), [
                'title' => 'Título atualizado',
                'content' => 'Descrição atualizada',
                'user_id' => $admin->id,
                'delivery_at' => '12/04/2026',
                'status' => 'pen',
                'editing_task_id' => $task->id,
            ])
            ->assertRedirect(route('tasks.index', ['task' => $task->id]));

        expect($task->fresh()->delivery_at?->format('Y-m-d'))->toBe('2026-04-12');

        $this->get(route('tasks.index', ['task' => $task->id]))->assertOk();
    });

    it('renderiza /tasks?task=ID quando old(delivery_at) está no formato ISO (regressão Carbon)', function () {
        $admin = actingAsAdmin();

        $task = Task::factory()->create([
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
            'title' => 'Tarefa existente',
            'content' => 'Conteúdo',
        ]);

        // Simula withInput() após exceção: delivery_at em Y-m-d na session old input.
        $this->withSession(['_old_input' => ['delivery_at' => '2026-04-12', 'editing_task_id' => $task->id]])
            ->get(route('tasks.index', ['task' => $task->id]))
            ->assertOk();
    });

    it('bloqueia edição inline quando o usuário não pode modificar a tarefa', function () {
        $owner = User::factory()->agent()->create();
        actingAsAgent();

        $task = Task::factory()->create([
            'user_id' => $owner->id,
            'author_id' => $owner->id,
            'status' => 'pen',
            'title' => 'Tarefa protegida',
        ]);

        $this->from(route('tasks.index'))
            ->patch(route('tasks.update', $task), [
                'title' => 'Tentativa indevida',
                'content' => 'Não deveria salvar',
                'user_id' => $owner->id,
                'status' => 'pro',
                'editing_task_id' => $task->id,
            ])
            ->assertRedirect(route('tasks.index'))
            ->assertSessionHas('warning', 'Você não tem permissão para editar esta tarefa.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa protegida',
            'status' => 'pen',
        ]);
    });
});
