<?php

/**
 * Testes de integração — NotificationRepository.
 * Usa RefreshDatabase para garantir isolamento.
 */

use App\Models\Tasks\Notification;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Repositories\NotificationRepository;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function nr_notification(int $userId, array $attrs = []): Notification
{
    $task = Task::factory()->create();

    return Notification::create(array_merge([
        'ref_id'    => $task->id,
        'content'   => 'Notificação de teste',
        'kind'      => 'new_task',
        'author_id' => $userId,
        'user_id'   => $userId,
        'seen'      => 0,
    ], $attrs));
}

// ─── create ───────────────────────────────────────────────────────────────────

describe('NotificationRepository — create', function () {

    it('persiste e retorna a notificação criada', function () {
        $user = User::factory()->create();
        $task = Task::factory()->create();
        $repo = new NotificationRepository();

        $notification = $repo->create([
            'ref_id'    => $task->id,
            'content'   => 'Nova tarefa atribuída',
            'kind'      => 'new_task',
            'author_id' => $user->id,
            'user_id'   => $user->id,
        ]);

        expect($notification)->toBeInstanceOf(Notification::class)
            ->and($notification->id)->not->toBeNull()
            ->and($notification->user_id)->toBe($user->id);

        $this->assertDatabaseHas('tasks_notification', ['id' => $notification->id]);
    });

});

// ─── getForUser ───────────────────────────────────────────────────────────────

describe('NotificationRepository — getForUser', function () {

    it('retorna as notificações do usuário sem filtro de ativas', function () {
        $user = User::factory()->create();
        nr_notification($user->id);
        nr_notification($user->id, ['seen' => 1]);

        $repo          = new NotificationRepository();
        $notifications = $repo->getForUser($user->id, false);

        expect($notifications->count())->toBeGreaterThanOrEqual(2)
            ->and($notifications->every(fn ($n) => $n->user_id === $user->id))->toBeTrue();
    });

    it('filtra apenas não-vistas recentes quando onlyActive = true', function () {
        $user = User::factory()->create();
        // Vistas → não deve aparecer no filtro ativo
        nr_notification($user->id, ['seen' => 1]);
        // Não vista e recente → deve aparecer
        nr_notification($user->id, ['seen' => 0, 'created_at' => now()->subMinutes(10)]);

        $repo          = new NotificationRepository();
        $notifications = $repo->getForUser($user->id, true);

        expect($notifications->every(fn ($n) => $n->seen == 0))->toBeTrue();
    });

    it('retorna no máximo 8 notificações', function () {
        $user = User::factory()->create();
        for ($i = 0; $i < 10; $i++) {
            nr_notification($user->id);
        }

        $repo          = new NotificationRepository();
        $notifications = $repo->getForUser($user->id, false);

        expect($notifications->count())->toBeLessThanOrEqual(8);
    });

    it('carrega o relacionamento task via eager loading', function () {
        $user = User::factory()->create();
        nr_notification($user->id);

        $repo          = new NotificationRepository();
        $notifications = $repo->getForUser($user->id, false);

        // O relacionamento task deve estar carregado (not lazy)
        expect($notifications->first()->relationLoaded('task'))->toBeTrue();
    });

});

// ─── findForUser ──────────────────────────────────────────────────────────────

describe('NotificationRepository — findForUser', function () {

    it('retorna a notificação correta pelo id e user_id', function () {
        $user         = User::factory()->create();
        $notification = nr_notification($user->id);

        $repo  = new NotificationRepository();
        $found = $repo->findForUser($notification->id, $user->id);

        expect($found->id)->toBe($notification->id);
    });

    it('lança ModelNotFoundException para notificação de outro usuário', function () {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $n     = nr_notification($user1->id);

        $repo = new NotificationRepository();

        expect(fn () => $repo->findForUser($n->id, $user2->id))
            ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
    });

});

// ─── markSeen ─────────────────────────────────────────────────────────────────

describe('NotificationRepository — markSeen', function () {

    it('atualiza seen para 1 no banco', function () {
        $user         = User::factory()->create();
        $notification = nr_notification($user->id, ['seen' => 0]);

        $repo   = new NotificationRepository();
        $result = $repo->markSeen($notification);

        expect($result)->toBeTrue();
        $this->assertDatabaseHas('tasks_notification', [
            'id'   => $notification->id,
            'seen' => 1,
        ]);
    });

});
