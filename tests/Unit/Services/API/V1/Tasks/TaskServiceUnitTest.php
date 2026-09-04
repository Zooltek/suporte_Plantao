<?php

/**
 * Testes UNITÁRIOS do TaskService — TaskRepositoryInterface mockado.
 * Testam exclusivamente regras de negócio: montagem do payload, is_open/can_modify,
 * notificações, lógica de status (started_at, completed_at, can/bin).
 */

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Events\Tasks\TaskStatusChanged;
use App\Models\Tasks\Task;
use App\Services\API\V1\Tasks\TaskService;
use Illuminate\Support\Facades\Event;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function tsu_repo(): MockInterface
{
    return Mockery::mock(TaskRepositoryInterface::class);
}

function tsu_service(MockInterface $repo): TaskService
{
    return new TaskService($repo);
}

/** Cria uma Task sem passar pelo fillable do Eloquent. */
function tsu_task(array $attrs = []): Task
{
    $task = new Task();
    foreach (array_merge([
        'id'        => 1,
        'title'     => 'Tarefa Teste',
        'status'    => 'pen',
        'user_id'   => 1,
        'author_id' => 1,
        'tester_id' => null,
    ], $attrs) as $key => $value) {
        $task->$key = $value;
    }
    return $task;
}

/** Relaxa todas as chamadas não-testadas para não quebrar por efeito colateral. */
function tsu_permissive(MockInterface $repo): void
{
    $repo->shouldReceive('createNotification')->andReturn(null)->byDefault();
    $repo->shouldReceive('save')->andReturn(true)->byDefault();
    $repo->shouldReceive('syncLabels')->andReturn(null)->byDefault();
    $repo->shouldReceive('findWithRelations')->andReturnUsing(fn($id) => tsu_task(['id' => $id]))->byDefault();
}

// ─── createTask — montagem do payload ─────────────────────────────────────────

describe('TaskService (unit) — createTask payload', function () {

    it('sanitiza o título com strip_tags e html_entity_decode', function () {
        $repo = tsu_repo();
        tsu_permissive($repo);

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn($d) => $d['title'] === 'Tarefa Simples'))
            ->andReturn(tsu_task(['title' => 'Tarefa Simples']));

        tsu_service($repo)->createTask([
            'title'     => '<b>Tarefa Simples</b>',
            'user_id'   => 1,
            'author_id' => 1,
        ]);
    });

    it('usa status "pen" por padrão quando não informado', function () {
        $repo = tsu_repo();
        tsu_permissive($repo);

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn($d) => $d['status'] === 'pen'))
            ->andReturn(tsu_task());

        tsu_service($repo)->createTask([
            'title'     => 'Tarefa',
            'user_id'   => 1,
            'author_id' => 1,
        ]);
    });

    it('converte software_id vazio para null', function () {
        $repo = tsu_repo();
        tsu_permissive($repo);

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn($d) => $d['software_id'] === null))
            ->andReturn(tsu_task());

        tsu_service($repo)->createTask([
            'title'       => 'Tarefa',
            'software_id' => '',
            'user_id'     => 1,
            'author_id'   => 1,
        ]);
    });

    it('sincroniza labels quando fornecidas', function () {
        $repo = tsu_repo();
        $task = tsu_task(['id' => 5]);

        tsu_permissive($repo);
        $repo->shouldReceive('create')->andReturn($task);
        $repo->shouldReceive('syncLabels')->once()->with($task, [10, 20]);

        tsu_service($repo)->createTask([
            'title'     => 'Tarefa',
            'user_id'   => 1,
            'author_id' => 1,
            'labels'    => [10, 20],
        ]);
    });

    it('não chama syncLabels quando labels não fornecidas', function () {
        $repo = tsu_repo();
        tsu_permissive($repo);

        $repo->shouldReceive('create')->andReturn(tsu_task());
        $repo->shouldNotReceive('syncLabels');

        tsu_service($repo)->createTask([
            'title'     => 'Tarefa',
            'user_id'   => 1,
            'author_id' => 1,
        ]);
    });

    it('gera notificação para responsável diferente do autor', function () {
        $repo = tsu_repo();
        $task = tsu_task(['id' => 1, 'user_id' => 99, 'author_id' => 1, 'tester_id' => null]);

        tsu_permissive($repo);
        $repo->shouldReceive('create')->andReturn($task);
        $repo->shouldReceive('createNotification')
            ->once()
            ->with(Mockery::on(fn($d) => $d['user_id'] === 99 && $d['kind'] === 'new_task'));

        tsu_service($repo)->createTask([
            'title'     => 'Tarefa',
            'user_id'   => 99,
            'author_id' => 1,
        ]);
    });

    it('não gera notificação quando responsável é o próprio autor', function () {
        $repo = tsu_repo();
        $task = tsu_task(['id' => 1, 'user_id' => 1, 'author_id' => 1, 'tester_id' => null]);

        tsu_permissive($repo);
        $repo->shouldReceive('create')->andReturn($task);
        $repo->shouldNotReceive('createNotification');

        tsu_service($repo)->createTask([
            'title'     => 'Tarefa',
            'user_id'   => 1,
            'author_id' => 1,
        ]);
    });

});

// ─── updateTaskStatus ──────────────────────────────────────────────────────────

describe('TaskService (unit) — updateTaskStatus', function () {

    it('lança Exception 403 quando task não pode ser modificada', function () {
        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id = 1;
        $task->shouldReceive('canModify')->andReturn(false);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);

        expect(fn() => tsu_service($repo)->updateTaskStatus(1, ['status' => 'don']))
            ->toThrow(\Exception::class);
    });

    it('define started_at quando status muda para "pro"', function () {
        Event::fake();

        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id         = 1;
        $task->status     = 'pen';
        $task->started_at = null;
        $task->author_id  = 1;
        $task->user_id    = 1;
        $task->tester_id  = null;
        $task->title      = 'T';
        $task->shouldReceive('canModify')->andReturn(true);
        $task->shouldReceive('fill')->andReturnSelf();

        $repo->shouldReceive('findOrFail')->andReturn($task);
        $repo->shouldReceive('save')->andReturn(true);
        $repo->shouldReceive('createNotification')->andReturn(null);

        tsu_service($repo)->updateTaskStatus(1, ['status' => 'pro']);

        expect($task->started_at)->not->toBeNull();
    });

    it('define completed_at quando status é "don"', function () {
        Event::fake();

        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id           = 1;
        $task->status       = 'pro';
        $task->started_at   = now()->subHour();
        $task->completed_at = null;
        $task->author_id    = 1;
        $task->user_id      = 1;
        $task->tester_id    = null;
        $task->title        = 'T';
        $task->shouldReceive('canModify')->andReturn(true);
        $task->shouldReceive('fill')->andReturnSelf();

        Event::fake();
        $repo->shouldReceive('findOrFail')->andReturn($task);
        $repo->shouldReceive('save')->andReturn(true);
        $repo->shouldReceive('createNotification')->andReturn(null);

        tsu_service($repo)->updateTaskStatus(1, ['status' => 'don']);

        expect($task->completed_at)->not->toBeNull();
    });

    it('dispara evento TaskStatusChanged', function () {
        Event::fake();

        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id         = 1;
        $task->status     = 'pen';
        $task->started_at = null;
        $task->author_id  = 1;
        $task->user_id    = 1;
        $task->tester_id  = null;
        $task->title      = 'T';
        $task->shouldReceive('canModify')->andReturn(true);
        $task->shouldReceive('fill')->andReturnSelf();

        $repo->shouldReceive('findOrFail')->andReturn($task);
        $repo->shouldReceive('save')->andReturn(true);
        $repo->shouldReceive('createNotification')->andReturn(null);

        tsu_service($repo)->updateTaskStatus(1, ['status' => 'pro']);

        Event::assertDispatched(TaskStatusChanged::class);
    });

});

// ─── updateTask ───────────────────────────────────────────────────────────────

describe('TaskService (unit) — updateTask', function () {

    it('lança Exception 403 quando a tarefa não pode ser editada', function () {
        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id = 1;
        $task->shouldReceive('canModify')->andReturn(false);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);

        expect(fn() => tsu_service($repo)->updateTask(1, [
            'title' => 'Atualizada',
            'content' => 'Nova descrição',
            'user_id' => 1,
            'status' => 'pro',
        ]))->toThrow(\Exception::class);
    });

    it('atualiza campos principais e started_at ao editar a tarefa', function () {
        Event::fake();

        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id = 1;
        $task->status = 'pen';
        $task->started_at = null;
        $task->completed_at = null;
        $task->author_id = 1;
        $task->user_id = 1;
        $task->tester_id = null;
        $task->title = 'Original';
        $task->shouldReceive('canModify')->andReturn(true);
        $task->shouldReceive('fill')->once()->andReturnUsing(function (array $payload) use ($task) {
            foreach ($payload as $key => $value) {
                $task->$key = $value;
            }

            return $task;
        });

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);
        $repo->shouldReceive('save')->once()->andReturn(true);
        $repo->shouldReceive('findWithRelations')->with(1)->andReturn($task);
        $repo->shouldReceive('createNotification')->andReturn(null);

        $result = tsu_service($repo)->updateTask(1, [
            'title' => '<b>Tarefa Atualizada</b>',
            'content' => 'Nova descrição',
            'user_id' => 8,
            'customer_id' => 15,
            'classification' => 'fix',
            'delivery_at' => '12/04/2026',
            'status' => 'pro',
        ]);

        expect($result->title)->toBe('Tarefa Atualizada')
            ->and($result->content)->toBe('Nova descrição')
            ->and($result->user_id)->toBe(8)
            ->and($result->customer_id)->toBe(15)
            ->and($result->classification)->toBe('fix')
            ->and($result->status)->toBe('pro')
            ->and($result->started_at)->not->toBeNull()
            ->and($result->delivery_at?->format('d/m/Y'))->toBe('12/04/2026');

        Event::assertDispatched(TaskStatusChanged::class);
    });

});

// ─── destroyTask ──────────────────────────────────────────────────────────────

describe('TaskService (unit) — destroyTask', function () {

    it('status vai para "can" quando não estava cancelado', function () {
        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id     = 1;
        $task->status = 'pen';
        $task->shouldReceive('canModify')->andReturn(true);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);
        $repo->shouldReceive('save')->once()->andReturn(true);

        tsu_service($repo)->destroyTask(1);

        expect($task->status)->toBe('can');
    });

    it('status vai para "bin" quando já estava cancelado', function () {
        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id     = 1;
        $task->status = 'can';
        $task->shouldReceive('canModify')->andReturn(true);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);
        $repo->shouldReceive('save')->once()->andReturn(true);

        tsu_service($repo)->destroyTask(1);

        expect($task->status)->toBe('bin');
    });

    it('lança Exception 403 quando task não pode ser modificada', function () {
        $repo = tsu_repo();
        $task = Mockery::mock(Task::class)->makePartial();
        $task->id = 1;
        $task->shouldReceive('canModify')->andReturn(false);

        $repo->shouldReceive('findOrFail')->with(1)->andReturn($task);

        expect(fn() => tsu_service($repo)->destroyTask(1))->toThrow(\Exception::class);
    });

});

// ─── getTasks — appendMetadata ─────────────────────────────────────────────────

describe('TaskService (unit) — getTasks appendMetadata', function () {

    it('marca is_open=true para status abertos', function () {
        $repo = tsu_repo();

        $task = new Task();
        $task->status = 'pen';
        $task->id     = 1;
        $collection = new \Illuminate\Database\Eloquent\Collection([$task]);

        $repo->shouldReceive('getFiltered')->andReturn($collection);

        $tasks = tsu_service($repo)->getTasks([]);

        expect($tasks->first()->is_open)->toBeTrue();
    });

    it('marca is_open=false para status fechados', function () {
        $repo = tsu_repo();

        $task = new Task();
        $task->status = 'don';
        $task->id     = 1;
        $collection = new \Illuminate\Database\Eloquent\Collection([$task]);

        $repo->shouldReceive('getFiltered')->andReturn($collection);

        $tasks = tsu_service($repo)->getTasks([]);

        expect($tasks->first()->is_open)->toBeFalse();
    });

    it('retorna diretamente via findWithRelations quando id é informado', function () {
        $repo = tsu_repo();

        $task = new Task();
        $task->id = 42;

        $repo->shouldReceive('findWithRelations')->with(42)->once()->andReturn($task);
        $repo->shouldNotReceive('getFiltered');

        $result = tsu_service($repo)->getTasks([], 42);

        expect($result->id)->toBe(42);
    });

});
