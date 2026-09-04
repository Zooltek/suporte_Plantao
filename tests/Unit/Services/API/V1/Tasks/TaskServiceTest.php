<?php

use App\Models\Software;
use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\API\V1\Tasks\TaskService;

// ─── Helpers ─────────────────────────────────────────────────────────────────

function ts_user(): User
{
    return User::factory()->create();
}

function ts_software(): Software
{
    return Software::factory()->create();
}

function ts_label(): Label
{
    return Label::factory()->create(['is_active' => true, 'parent_id' => null]);
}

function ts_payload(array $overrides = []): array
{
    $user = ts_user();

    return array_merge([
        'title'     => 'Tarefa de teste',
        'content'   => 'Descrição da tarefa',
        'user_id'   => $user->id,
        'author_id' => $user->id,
    ], $overrides);
}

// ─── createTask ──────────────────────────────────────────────────────────────

describe('TaskService — createTask', function () {

    it('cria tarefa com software_id e persiste corretamente', function () {
        $software = ts_software();

        $task = app(TaskService::class)->createTask(
            ts_payload(['software_id' => $software->id])
        );

        expect($task->software_id)->toBe($software->id);
        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'software_id' => $software->id]);
    });

    it('cria tarefa sem software_id quando não informado', function () {
        $task = app(TaskService::class)->createTask(ts_payload());

        expect($task->software_id)->toBeNull();
    });

    it('persiste título e conteúdo corretamente', function () {
        $task = app(TaskService::class)->createTask(
            ts_payload(['title' => 'Correção de Bug', 'content' => 'Detalhes do bug'])
        );

        expect($task->title)->toBe('Correção de Bug')
            ->and($task->content)->toBe('Detalhes do bug');
    });

    it('sincroniza módulos (labels) ao criar tarefa', function () {
        $label1 = ts_label();
        $label2 = ts_label();

        $task = app(TaskService::class)->createTask(
            ts_payload(['labels' => [$label1->id, $label2->id]])
        );

        expect($task->labels->pluck('id')->toArray())
            ->toContain($label1->id)
            ->toContain($label2->id);
    });

    it('cria tarefa sem módulos quando labels não informados', function () {
        $task = app(TaskService::class)->createTask(ts_payload());

        expect($task->labels)->toBeEmpty();
    });

    it('carrega relação software após criar', function () {
        $software = ts_software();

        $task = app(TaskService::class)->createTask(
            ts_payload(['software_id' => $software->id])
        );

        expect($task->software)->not->toBeNull()
            ->and($task->software->name)->toBe($software->name);
    });

});

// ─── getTasks (filtro software_id) ───────────────────────────────────────────

describe('TaskService — getTasks filtro software_id', function () {

    it('filtra tarefas pelo sistema informado', function () {
        $soft1 = ts_software();
        $soft2 = ts_software();
        $user  = ts_user();

        Task::factory()->withSoftware($soft1)->create(['user_id' => $user->id, 'author_id' => $user->id]);
        Task::factory()->withSoftware($soft1)->create(['user_id' => $user->id, 'author_id' => $user->id]);
        Task::factory()->withSoftware($soft2)->create(['user_id' => $user->id, 'author_id' => $user->id]);

        $tasks = app(TaskService::class)->getTasks(['software_id' => $soft1->id]);

        expect($tasks->pluck('software_id')->unique()->toArray())->toEqual([$soft1->id]);
    });

    it('sem filtro software_id retorna tarefas de todos os sistemas', function () {
        $user  = ts_user();
        $soft1 = ts_software();
        $soft2 = ts_software();

        Task::factory()->withSoftware($soft1)->create(['user_id' => $user->id, 'author_id' => $user->id]);
        Task::factory()->withSoftware($soft2)->create(['user_id' => $user->id, 'author_id' => $user->id]);

        $tasks = app(TaskService::class)->getTasks([]);

        $softwareIds = $tasks->pluck('software_id')->filter()->unique();
        expect($softwareIds->count())->toBeGreaterThanOrEqual(2);
    });

});

// ─── getTasks (filtro label_id) ───────────────────────────────────────────────

describe('TaskService — getTasks filtro label_id', function () {

    it('filtra tarefas pelo módulo (label) informado', function () {
        $user   = ts_user();
        $label1 = ts_label();
        $label2 = ts_label();

        $taskA = Task::factory()->create(['user_id' => $user->id, 'author_id' => $user->id]);
        $taskA->labels()->sync([$label1->id]);

        $taskB = Task::factory()->create(['user_id' => $user->id, 'author_id' => $user->id]);
        $taskB->labels()->sync([$label2->id]);

        $tasks = app(TaskService::class)->getTasks(['label_id' => $label1->id]);

        expect($tasks->pluck('id'))->toContain($taskA->id)
            ->and($tasks->pluck('id'))->not->toContain($taskB->id);
    });

    it('sem filtro label_id retorna tarefas de todos os módulos', function () {
        $user   = ts_user();
        $label1 = ts_label();
        $label2 = ts_label();

        $taskA = Task::factory()->create(['user_id' => $user->id, 'author_id' => $user->id]);
        $taskA->labels()->sync([$label1->id]);

        $taskB = Task::factory()->create(['user_id' => $user->id, 'author_id' => $user->id]);
        $taskB->labels()->sync([$label2->id]);

        $tasks = app(TaskService::class)->getTasks([]);

        expect($tasks->pluck('id'))
            ->toContain($taskA->id)
            ->toContain($taskB->id);
    });

});

// ─── getTasks (filtro datas) ──────────────────────────────────────────────────

describe('TaskService — getTasks filtros de data', function () {

    it('filtro start exclui tarefas anteriores à data', function () {
        $user = ts_user();

        $old = Task::factory()->create([
            'user_id'    => $user->id,
            'author_id'  => $user->id,
            'created_at' => now()->subDays(30),
            'updated_at' => now()->subDays(30),
        ]);

        $recent = Task::factory()->create([
            'user_id'    => $user->id,
            'author_id'  => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = app(TaskService::class)->getTasks(['start' => now()->subDays(7)->toDateString()]);

        expect($tasks->pluck('id'))
            ->toContain($recent->id)
            ->not->toContain($old->id);
    });

    it('filtro end exclui tarefas posteriores à data', function () {
        $user = ts_user();

        $old = Task::factory()->create([
            'user_id'    => $user->id,
            'author_id'  => $user->id,
            'created_at' => now()->subDays(10),
            'updated_at' => now()->subDays(10),
        ]);

        $recent = Task::factory()->create([
            'user_id'    => $user->id,
            'author_id'  => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $tasks = app(TaskService::class)->getTasks(['end' => now()->subDays(5)->toDateString()]);

        expect($tasks->pluck('id'))
            ->toContain($old->id)
            ->not->toContain($recent->id);
    });

    it('filtro delivery_end exclui tarefas com entrega posterior', function () {
        $user = ts_user();

        $far = Task::factory()->create([
            'user_id'    => $user->id,
            'author_id'  => $user->id,
            'delivery_at' => now()->addDays(30),
        ]);

        $near = Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'delivery_at' => now()->addDays(3),
        ]);

        $tasks = app(TaskService::class)->getTasks([
            'delivery_end' => now()->addDays(7)->toDateString(),
        ]);

        expect($tasks->pluck('id'))
            ->toContain($near->id)
            ->not->toContain($far->id);
    });

});
