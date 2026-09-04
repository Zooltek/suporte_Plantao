<?php

/**
 * Testes UNITÁRIOS — NotificationService.
 * O NotificationRepositoryInterface é mockado; nenhum DB é tocado.
 */

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Models\Tasks\Notification;
use App\Models\Tasks\Task;
use App\Services\API\V1\Tasks\NotificationService;
use Illuminate\Support\Facades\Bus;
use Mockery\MockInterface;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ns_repo(): MockInterface
{
    return Mockery::mock(NotificationRepositoryInterface::class);
}

function ns_service(MockInterface $repo): NotificationService
{
    return new NotificationService($repo);
}

function ns_notification(array $attrs = []): Notification
{
    $n = new Notification();
    foreach (array_merge([
        'id'         => 1,
        'user_id'    => 1,
        'seen'       => 0,
        'created_at' => now(),
        'ref_id'     => 1,
        'kind'       => 'new_task',
        'author_id'  => 1,
        'content'    => 'Notificação',
    ], $attrs) as $k => $v) {
        $n->$k = $v;
    }
    return $n;
}

// ─── createNotification ───────────────────────────────────────────────────────

describe('NotificationService (unit) — createNotification', function () {

    it('chama repository->create com o payload correto e retorna a notificação', function () {
        Bus::fake();

        $repo = ns_repo();
        $n    = ns_notification();

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) =>
                $d['ref_id']    === 1
                && $d['content']  === 'Nova tarefa'
                && $d['kind']     === 'new_task'
                && $d['author_id'] === 2
                && $d['user_id']   === 3
                && isset($d['icon'])
            ))
            ->andReturn($n);

        $result = ns_service($repo)->createNotification([
            'ref_id'    => 1,
            'content'   => 'Nova tarefa',
            'kind'      => 'new_task',
            'author_id' => 2,
            'user_id'   => 3,
        ]);

        expect($result)->toBeInstanceOf(Notification::class);
    });

    it('usa ícone "task-new.png" para status "pen"', function () {
        Bus::fake();

        $repo = ns_repo();
        $n    = ns_notification();

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['icon'] === 'task-new.png'))
            ->andReturn($n);

        ns_service($repo)->createNotification([
            'ref_id'    => 1,
            'content'   => 'x',
            'kind'      => 'x',
            'author_id' => 1,
            'user_id'   => 1,
            'status'    => 'pen',
        ]);
    });

    it('usa ícone "task-done.png" para status "don"', function () {
        Bus::fake();

        $repo = ns_repo();
        $n    = ns_notification();

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['icon'] === 'task-done.png'))
            ->andReturn($n);

        ns_service($repo)->createNotification([
            'ref_id'    => 1,
            'content'   => 'x',
            'kind'      => 'x',
            'author_id' => 1,
            'user_id'   => 1,
            'status'    => 'don',
        ]);
    });

    it('usa ícone "task-default.png" para status desconhecido', function () {
        Bus::fake();

        $repo = ns_repo();
        $n    = ns_notification();

        $repo->shouldReceive('create')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['icon'] === 'task-default.png'))
            ->andReturn($n);

        ns_service($repo)->createNotification([
            'ref_id'    => 1,
            'content'   => 'x',
            'kind'      => 'x',
            'author_id' => 1,
            'user_id'   => 1,
        ]);
    });

});

// ─── getNotifications ─────────────────────────────────────────────────────────

describe('NotificationService (unit) — getNotifications', function () {

    it('chama repositório com os parâmetros corretos', function () {
        $repo = ns_repo();
        $n    = ns_notification();
        $n->task = null;
        $collection = new \Illuminate\Database\Eloquent\Collection([$n]);

        $repo->shouldReceive('getForUser')
            ->once()
            ->with(7, true)
            ->andReturn($collection);

        $result = ns_service($repo)->getNotifications(7, true);

        expect($result->count())->toBe(1);
    });

    it('enriquece cada notificação com icon, url e timestamp', function () {
        $repo = ns_repo();
        $n    = ns_notification(['created_at' => now()->subMinutes(5)]);
        $n->task = null;
        $collection = new \Illuminate\Database\Eloquent\Collection([$n]);

        $repo->shouldReceive('getForUser')->andReturn($collection);

        $result = ns_service($repo)->getNotifications(1, false);
        $first  = $result->first();

        expect($first->icon)->not->toBeNull()
            ->and($first->url)->toBe('ae')
            ->and($first->timestamp)->not->toBeNull();
    });

});

// ─── markAsSeen ──────────────────────────────────────────────────────────────

describe('NotificationService (unit) — markAsSeen', function () {

    it('chama markSeen e retorna true para notificação não vista', function () {
        $repo = ns_repo();
        $n    = ns_notification(['seen' => 0]);

        $repo->shouldReceive('findForUser')->with(1, 5)->andReturn($n);
        $repo->shouldReceive('markSeen')->with($n)->once()->andReturn(true);

        $result = ns_service($repo)->markAsSeen(1, 5);

        expect($result)->toBeTrue();
    });

    it('lança Exception 422 quando a notificação já foi vista', function () {
        $repo = ns_repo();
        $n    = ns_notification(['seen' => 1]);

        $repo->shouldReceive('findForUser')->andReturn($n);
        $repo->shouldNotReceive('markSeen');

        expect(fn () => ns_service($repo)->markAsSeen(1, 5))
            ->toThrow(\Exception::class, 'Already seen');
    });

});
