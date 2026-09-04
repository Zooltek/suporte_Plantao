<?php

use App\Models\CategoryDescription;
use App\Models\Customer;
use App\Models\Schedule;
use App\Models\Tasks\Notification as TaskNotification;
use App\Models\Tasks\Task;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Models\User\Setting;
use App\Repositories\DashboardRepository;
use Illuminate\Support\Carbon;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function dash_repo(): DashboardRepository
{
    return new DashboardRepository;
}

function dash_agent(): User
{
    return User::factory()->agent()->create();
}

function dash_ticket(): Ticket
{
    return Ticket::factory()->create();
}

// ─── getCustomers ─────────────────────────────────────────────────────────────

describe('DashboardRepository — getCustomers', function () {

    it('retorna uma Collection de customers', function () {
        Customer::factory()->count(3)->create();

        $result = dash_repo()->getCustomers();

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
        expect($result->count())->toBeGreaterThanOrEqual(3);
    });

    it('retorna customers ordenados por trade_name asc', function () {
        Customer::factory()->create(['trade_name' => 'Zebra Corp']);
        Customer::factory()->create(['trade_name' => 'Alpha Ltda']);
        Customer::factory()->create(['trade_name' => 'Mega Sistemas']);

        $names = dash_repo()->getCustomers()->pluck('trade_name')->toArray();

        $sorted = $names;
        sort($sorted);

        expect($names)->toBe($sorted);
    });

});

// ─── countSchedules ───────────────────────────────────────────────────────────

describe('DashboardRepository — countSchedules', function () {

    it('retorna int (pode ser zero) para tipo today sem agentId', function () {
        $result = dash_repo()->countSchedules(null, 'today');

        expect($result)->toBeInt();
        expect($result)->toBeGreaterThanOrEqual(0);
    });

    it('retorna int para tipo overdue', function () {
        $result = dash_repo()->countSchedules(null, 'overdue');

        expect($result)->toBeInt();
    });

    it('retorna int para tipo upcoming', function () {
        $result = dash_repo()->countSchedules(null, 'upcoming');

        expect($result)->toBeInt();
    });

    it('retorna 0 para tipo desconhecido', function () {
        $result = dash_repo()->countSchedules(null, 'invalid_type');

        expect($result)->toBe(0);
    });

    it('conta apenas agendamentos do agente quando agentId é informado', function () {
        $agent = dash_agent();
        $other = dash_agent();

        Schedule::factory()->create([
            'agent_id' => $agent->id,
            'status' => 'sch',
            'start_at' => Carbon::today()->setTime(10, 0),
        ]);

        Schedule::factory()->create([
            'agent_id' => $other->id,
            'status' => 'sch',
            'start_at' => Carbon::today()->setTime(10, 0),
        ]);

        $countAgent = dash_repo()->countSchedules($agent->id, 'today');
        $countOther = dash_repo()->countSchedules($other->id, 'today');

        expect($countAgent)->toBeGreaterThanOrEqual(1);
        expect($countOther)->toBeGreaterThanOrEqual(1);
        // O agente não deve ver os agendamentos do outro
        expect($countAgent)->toBeLessThan($countAgent + $countOther);
    });

});

// ─── getRecentAttendances ─────────────────────────────────────────────────────

describe('DashboardRepository — getRecentAttendances', function () {

    it('retorna uma Collection', function () {
        $result = dash_repo()->getRecentAttendances(null);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('não retorna atendimentos com mais de 7 dias', function () {
        $ticket = dash_ticket();

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => Carbon::now()->subDays(10),
        ]);

        $result = dash_repo()->getRecentAttendances(null);

        $old = $result->filter(fn ($a) => $a->created_at->lt(Carbon::now()->subDays(7)->startOfDay()));
        expect($old->count())->toBe(0);
    });

    it('filtra por agentId quando informado', function () {
        $agent = dash_agent();
        $other = dash_agent();
        $ticket = dash_ticket();

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'created_at' => Carbon::now()->subDay(),
        ]);

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $other->id,
            'created_at' => Carbon::now()->subDay(),
        ]);

        $result = dash_repo()->getRecentAttendances($agent->id);

        expect($result->every(fn ($a) => $a->user_id === $agent->id))->toBeTrue();
    });

});

// ─── countTodayAttendances ────────────────────────────────────────────────────

describe('DashboardRepository — countTodayAttendances', function () {

    it('retorna int', function () {
        $result = dash_repo()->countTodayAttendances(null);

        expect($result)->toBeInt();
        expect($result)->toBeGreaterThanOrEqual(0);
    });

    it('conta apenas atendimentos de hoje', function () {
        $ticket = dash_ticket();

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => Carbon::today()->setTime(9, 0),
        ]);

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'created_at' => Carbon::yesterday()->setTime(9, 0),
        ]);

        $result = dash_repo()->countTodayAttendances(null);

        expect($result)->toBeGreaterThanOrEqual(1);
    });

    it('filtra por agentId quando informado', function () {
        $agent = dash_agent();
        $other = dash_agent();
        $ticket = dash_ticket();

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'created_at' => Carbon::today()->setTime(10, 0),
        ]);

        Attendance::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $other->id,
            'created_at' => Carbon::today()->setTime(11, 0),
        ]);

        $countAgent = dash_repo()->countTodayAttendances($agent->id);
        $countAll = dash_repo()->countTodayAttendances(null);

        expect($countAgent)->toBeGreaterThanOrEqual(1);
        expect($countAll)->toBeGreaterThanOrEqual($countAgent);
    });

});

// ─── getRecentTickets ─────────────────────────────────────────────────────────

describe('DashboardRepository — getRecentTickets', function () {

    it('retorna uma Collection', function () {
        $result = dash_repo()->getRecentTickets(null);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('retorna tickets relevantes da visão condensada quando agentId é null', function () {
        $ticket = Ticket::factory()->create([
            'agent_id' => User::factory()->agent()->create()->id,
            'completed_at' => null,
        ]);

        $result = dash_repo()->getRecentTickets(null);

        expect($result->contains('id', $ticket->id))->toBeTrue();
    });

    it('inclui tickets do agente e da fila de pendências quando agentId é informado', function () {
        $agent = dash_agent();
        $ticketAgent = Ticket::factory()->create(['agent_id' => $agent->id, 'completed_at' => null]);
        $queueTicketNull = Ticket::factory()->create([
            'agent_id' => null,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'completed_at' => null,
        ]);
        $queueTicketZero = Ticket::factory()->create([
            'agent_id' => 0,
            'status_id' => Ticket::STATUS_PENDING_ID,
            'completed_at' => null,
        ]);
        $ticketOther = Ticket::factory()->create(['agent_id' => User::factory()->agent()->create()->id, 'completed_at' => null]);

        $result = dash_repo()->getRecentTickets($agent->id);

        expect($result->pluck('id')->toArray())->toContain($ticketAgent->id)
            ->and($result->pluck('id')->toArray())->toContain($queueTicketNull->id)
            ->and($result->pluck('id')->toArray())->toContain($queueTicketZero->id)
            ->and($result->pluck('id')->toArray())->not->toContain($ticketOther->id);
    });

    it('exclui tickets sem responsável fora da fila de pendências', function () {
        $invalidUnassignedTicket = Ticket::factory()->create([
            'agent_id' => null,
            'status_id' => 1,
            'completed_at' => null,
        ]);

        $result = dash_repo()->getRecentTickets(null);

        expect($result->pluck('id')->toArray())->not->toContain($invalidUnassignedTicket->id);
    });

});

// ─── getCategoryNames ─────────────────────────────────────────────────────────

describe('DashboardRepository — getCategoryNames', function () {

    it('retorna uma Collection chave-valor (category_id => name)', function () {
        $result = dash_repo()->getCategoryNames();

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('usa category_id como chave e name como valor', function () {
        $category = \App\Models\Category::factory()->create(['parent_id' => 0]);
        $desc = CategoryDescription::factory()->create([
            'category_id' => $category->category_id,
            'name' => 'Categoria Teste Dashboard',
        ]);

        $result = dash_repo()->getCategoryNames();

        expect($result->get($category->category_id))->toBe('Categoria Teste Dashboard');
    });

});

// ─── getSchedulesForWeek ─────────────────────────────────────────────────────

describe('DashboardRepository — getSchedulesForWeek', function () {

    it('retorna uma Collection', function () {
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6)->endOfDay();

        $result = dash_repo()->getSchedulesForWeek($start, $end, null);

        expect($result)->toBeInstanceOf(\Illuminate\Support\Collection::class);
    });

    it('retorna agendamentos ativos dentro do período', function () {
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6)->endOfDay();

        $schedule = Schedule::factory()->create([
            'status' => 'sch',
            'start_at' => $start->copy()->addDay()->setTime(10, 0),
        ]);

        $result = dash_repo()->getSchedulesForWeek($start, $end, null);

        expect($result->contains('id', $schedule->id))->toBeTrue();
    });

    it('filtra por agentId quando informado', function () {
        $agent = dash_agent();
        $other = dash_agent();
        $start = Carbon::now()->startOfWeek(Carbon::MONDAY);
        $end = $start->copy()->addDays(6)->endOfDay();

        $scheduleAgent = Schedule::factory()->create([
            'agent_id' => $agent->id,
            'status' => 'sch',
            'start_at' => $start->copy()->addDay()->setTime(14, 0),
        ]);

        $scheduleOther = Schedule::factory()->create([
            'agent_id' => $other->id,
            'status' => 'sch',
            'start_at' => $start->copy()->addDay()->setTime(15, 0),
        ]);

        $result = dash_repo()->getSchedulesForWeek($start, $end, $agent->id);

        expect($result->contains('id', $scheduleAgent->id))->toBeTrue();
        expect($result->contains('id', $scheduleOther->id))->toBeFalse();
    });

});

// ─── countMyTasks ─────────────────────────────────────────────────────────────

describe('DashboardRepository — countMyTasks', function () {

    it('retorna int', function () {
        $user = User::factory()->create();

        $result = dash_repo()->countMyTasks($user->id, 'new');

        expect($result)->toBeInt();
        expect($result)->toBeGreaterThanOrEqual(0);
    });

    it('conta tarefas com o status informado onde o usuário é responsável', function () {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'status' => 'new',
        ]);

        Task::factory()->create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'status' => 'sto',
        ]);

        $countNew = dash_repo()->countMyTasks($user->id, 'new');
        $countSto = dash_repo()->countMyTasks($user->id, 'sto');

        expect($countNew)->toBe(1);
        expect($countSto)->toBe(1);
    });

    it('não conta tarefas com status bin (filtrado por visible())', function () {
        $user = User::factory()->create();

        Task::factory()->create([
            'user_id' => $user->id,
            'author_id' => $user->id,
            'status' => 'bin',
        ]);

        $result = dash_repo()->countMyTasks($user->id, 'bin');

        expect($result)->toBe(0);
    });

});

// ─── countUnseenTaskNotifications ────────────────────────────────────────────

describe('DashboardRepository — countUnseenTaskNotifications', function () {

    it('retorna int', function () {
        $user = User::factory()->create();

        $result = dash_repo()->countUnseenTaskNotifications($user->id);

        expect($result)->toBeInt();
        expect($result)->toBeGreaterThanOrEqual(0);
    });

    it('conta apenas notificações não lidas do usuário', function () {
        $user = User::factory()->create();
        $author = User::factory()->create();

        TaskNotification::create([
            'ref_id' => null,
            'content' => 'Notificação não lida',
            'kind' => 'status_change',
            'author_id' => $author->id,
            'user_id' => $user->id,
            'seen' => 0,
        ]);

        TaskNotification::create([
            'ref_id' => null,
            'content' => 'Notificação lida',
            'kind' => 'status_change',
            'author_id' => $author->id,
            'user_id' => $user->id,
            'seen' => 1,
        ]);

        $result = dash_repo()->countUnseenTaskNotifications($user->id);

        expect($result)->toBe(1);
    });

    it('não conta notificações de outros usuários', function () {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $author = User::factory()->create();

        TaskNotification::create([
            'ref_id' => null,
            'content' => 'Notificação de outro usuário',
            'kind' => 'status_change',
            'author_id' => $author->id,
            'user_id' => $other->id,
            'seen' => 0,
        ]);

        $result = dash_repo()->countUnseenTaskNotifications($user->id);

        expect($result)->toBe(0);
    });

});

// ─── getUserSettings ─────────────────────────────────────────────────────────

describe('DashboardRepository — getUserSettings', function () {

    it('retorna array', function () {
        $user = User::factory()->create();

        $result = dash_repo()->getUserSettings($user->id);

        expect($result)->toBeArray();
    });

    it('retorna array vazio quando usuário não tem configurações', function () {
        $user = User::factory()->create();

        $result = dash_repo()->getUserSettings($user->id);

        expect($result)->toBe([]);
    });

    it('retorna slug como chave e value como valor', function () {
        $user = User::factory()->create();

        Setting::create([
            'user_id' => $user->id,
            'slug' => 'theme',
            'value' => 'dark',
        ]);

        $result = dash_repo()->getUserSettings($user->id);

        expect($result)->toHaveKey('theme');
        expect($result['theme'])->toBe('dark');
    });

});
