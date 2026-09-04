<?php

use App\Models\Tasks\Notification as TaskNotification;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\Agent\DashboardService;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function ds_user(): User
{
    return User::factory()->create();
}

function ds_task(int $userId, string $status = 'new'): Task
{
    return Task::factory()->create([
        'user_id'   => $userId,
        'author_id' => $userId,
        'status'    => $status,
    ]);
}

function ds_notification(int $userId, int $seen = 0): TaskNotification
{
    $author = ds_user();

    return TaskNotification::create([
        'ref_id'    => null,
        'content'   => 'Tarefa atualizada',
        'kind'      => 'status_change',
        'author_id' => $author->id,
        'user_id'   => $userId,
        'seen'      => $seen,
    ]);
}

// ─── countMyTasks ─────────────────────────────────────────────────────────────

describe('DashboardService — countMyTasks', function () {

    it('conta tarefas novas do usuário', function () {
        $user = ds_user();
        ds_task($user->id, 'new');
        ds_task($user->id, 'new');
        ds_task($user->id, 'pen'); // outro status

        $count = app(DashboardService::class)->countMyTasks($user, 'new');

        expect($count)->toBe(2);
    });

    it('conta tarefas paradas do usuário', function () {
        $user = ds_user();
        ds_task($user->id, 'sto');
        ds_task($user->id, 'new'); // outro status

        $count = app(DashboardService::class)->countMyTasks($user, 'sto');

        expect($count)->toBe(1);
    });

    it('não conta tarefas de outros usuários', function () {
        $user  = ds_user();
        $other = ds_user();

        ds_task($other->id, 'new');

        $count = app(DashboardService::class)->countMyTasks($user, 'new');

        expect($count)->toBe(0);
    });

    it('conta tarefa em que usuário é tester', function () {
        $user   = ds_user();
        $author = ds_user();

        Task::factory()->create([
            'author_id' => $author->id,
            'user_id'   => $author->id,
            'tester_id' => $user->id,
            'status'    => 'new',
        ]);

        $count = app(DashboardService::class)->countMyTasks($user, 'new');

        expect($count)->toBe(1);
    });

    it('não conta tarefas excluídas (bin)', function () {
        $user = ds_user();
        ds_task($user->id, 'bin'); // bin é filtrado por visible()

        $count = app(DashboardService::class)->countMyTasks($user, 'bin');

        expect($count)->toBe(0);
    });

    it('retorna 0 quando usuário é null', function () {
        $count = app(DashboardService::class)->countMyTasks(null, 'new');

        expect($count)->toBe(0);
    });

});

// ─── countUnseenTaskNotifications ────────────────────────────────────────────

describe('DashboardService — countUnseenTaskNotifications', function () {

    it('conta notificações não lidas', function () {
        $user = ds_user();
        ds_notification($user->id, seen: 0);
        ds_notification($user->id, seen: 0);
        ds_notification($user->id, seen: 1); // já lida

        $count = app(DashboardService::class)->countUnseenTaskNotifications($user->id);

        expect($count)->toBe(2);
    });

    it('não conta notificações de outros usuários', function () {
        $user  = ds_user();
        $other = ds_user();
        ds_notification($other->id, seen: 0);

        $count = app(DashboardService::class)->countUnseenTaskNotifications($user->id);

        expect($count)->toBe(0);
    });

    it('retorna 0 quando userId é 0', function () {
        $count = app(DashboardService::class)->countUnseenTaskNotifications(0);

        expect($count)->toBe(0);
    });

});

// ─── countMyTasksDueToday ────────────────────────────────────────────────────

describe('DashboardService — countMyTasksDueToday', function () {

    it('conta tarefas com delivery_at hoje em que o usuário é responsável', function () {
        $user = ds_user();
        Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'status'      => 'pen',
            'delivery_at' => now()->startOfDay(),
        ]);
        Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'status'      => 'new',
            'delivery_at' => now()->endOfDay(),
        ]);

        $count = app(DashboardService::class)->countMyTasksDueToday($user->id);

        expect($count)->toBe(2);
    });

    it('ignora tarefas com delivery_at em outra data', function () {
        $user = ds_user();
        Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'status'      => 'pen',
            'delivery_at' => now()->subDay(),
        ]);
        Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'status'      => 'pen',
            'delivery_at' => now()->addDay(),
        ]);

        $count = app(DashboardService::class)->countMyTasksDueToday($user->id);

        expect($count)->toBe(0);
    });

    it('conta tarefa em que usuário é tester ou autor', function () {
        $user   = ds_user();
        $author = ds_user();

        Task::factory()->create([
            'author_id'   => $author->id,
            'user_id'     => $author->id,
            'tester_id'   => $user->id,
            'status'      => 'pen',
            'delivery_at' => now()->startOfDay(),
        ]);
        Task::factory()->create([
            'author_id'   => $user->id,
            'user_id'     => $author->id,
            'status'      => 'pen',
            'delivery_at' => now()->startOfDay(),
        ]);

        $count = app(DashboardService::class)->countMyTasksDueToday($user->id);

        expect($count)->toBe(2);
    });

    it('não conta tarefas concluídas/canceladas mesmo com delivery_at hoje', function () {
        $user = ds_user();

        foreach (['can', 'rej', 'bin'] as $status) {
            Task::factory()->create([
                'user_id'     => $user->id,
                'author_id'   => $user->id,
                'status'      => $status,
                'delivery_at' => now()->startOfDay(),
            ]);
        }

        $count = app(DashboardService::class)->countMyTasksDueToday($user->id);

        expect($count)->toBe(0);
    });

    it('ignora tarefas sem delivery_at definido', function () {
        $user = ds_user();
        Task::factory()->create([
            'user_id'     => $user->id,
            'author_id'   => $user->id,
            'status'      => 'pen',
            'delivery_at' => null,
        ]);

        $count = app(DashboardService::class)->countMyTasksDueToday($user->id);

        expect($count)->toBe(0);
    });

    it('retorna 0 quando userId é 0', function () {
        $count = app(DashboardService::class)->countMyTasksDueToday(0);

        expect($count)->toBe(0);
    });

});

// ─── resolveAgentId — Botão Mostrar Tudo (Bug fix) ───────────────────────────

describe('DashboardService — resolveAgentId via getCondensedData', function () {

    it('agente sem filtro só vê seus próprios agendamentos por padrão', function () {
        $agent = User::factory()->agent()->create();
        $other = User::factory()->agent()->create();

        // Segunda-feira da semana atual às 09:00 — garante slot no calendário
        $monday = now()->startOfWeek(\Carbon\Carbon::MONDAY)->setTime(9, 0);

        \App\Models\Schedule::factory()->create(['agent_id' => $agent->id, 'status' => 'con', 'start_at' => $monday]);
        \App\Models\Schedule::factory()->create(['agent_id' => $other->id, 'status' => 'con', 'start_at' => $monday->copy()->addHour()]);

        \Illuminate\Support\Facades\Auth::guard('admin')->login($agent);

        $data = app(\App\Services\Agent\DashboardService::class)
            ->getCondensedData($agent, ['active' => 'schedules']);

        $found = collect($data['schedules_data'])
            ->flatMap(fn ($day) => array_filter(array_merge($day['morning'], $day['afternoon'])));

        expect($found)->not->toBeEmpty()
            ->and($found->pluck('agent_id')->unique()->values()->all())->toBe([$agent->id]);
    });

    it('agent_id=0 remove o filtro e retorna agendamentos de todos os agentes', function () {
        $agent = User::factory()->agent()->create();
        $other = User::factory()->agent()->create();

        $monday = now()->startOfWeek(\Carbon\Carbon::MONDAY);

        // Um de manhã, outro de tarde — evita colisão de slot no calendário
        // status='con' para garantir que passam pelo scopeActive (exclui apenas 'del')
        \App\Models\Schedule::factory()->create(['agent_id' => $agent->id, 'status' => 'con', 'start_at' => $monday->copy()->setTime(9, 0)]);
        \App\Models\Schedule::factory()->create(['agent_id' => $other->id, 'status' => 'con', 'start_at' => $monday->copy()->setTime(14, 0)]);

        \Illuminate\Support\Facades\Auth::guard('admin')->login($agent);

        $data = app(\App\Services\Agent\DashboardService::class)
            ->getCondensedData($agent, ['active' => 'schedules', 'agent_id' => '0']);

        $found = collect($data['schedules_data'])
            ->flatMap(fn ($day) => array_filter(array_merge($day['morning'], $day['afternoon'])));

        $agentIds = $found->pluck('agent_id')->unique()->values()->all();

        expect($agentIds)->toContain($agent->id)->toContain($other->id);
    });

});
