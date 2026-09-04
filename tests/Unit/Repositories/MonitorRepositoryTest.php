<?php

use App\Models\Ticket\Ticket;
use App\Models\User;
use App\Repositories\MonitorRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

// ─── Helpers ──────────────────────────────────────────────────────────────────

function mr_agent(array $attrs = []): User
{
    return User::factory()->agent()->create(array_merge([
        'active'        => true,
        'department_id' => 1,
    ], $attrs));
}

function mr_ticket(int $userId, array $attrs = []): Ticket
{
    return Ticket::factory()->create(array_merge([
        'user_id' => $userId,
    ], $attrs));
}

// ─── Testes: getAgentsWithStats ────────────────────────────────────────────────

describe('MonitorRepository — getAgentsWithStats', function () {

    it('retorna apenas agentes ativos excluindo departamentos 3 e 4', function () {
        $agent1 = mr_agent(['department_id' => 1]);
        $agent2 = mr_agent(['department_id' => 2]);
        $hidden = mr_agent(['department_id' => 3]);

        $repo   = new MonitorRepository();
        $start  = Carbon::now()->subDay();
        $end    = Carbon::now()->addDay();
        $now    = Carbon::now();

        $result = $repo->getAgentsWithStats($start, $end, $now);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($agent1->id)
            ->and($ids)->toContain($agent2->id)
            ->and($ids)->not->toContain($hidden->id);
    });

    it('não retorna agentes inativos', function () {
        $active   = mr_agent(['active' => true, 'department_id' => 1]);
        $inactive = mr_agent(['active' => false, 'department_id' => 1]);

        $repo   = new MonitorRepository();
        $start  = Carbon::now()->subDay();
        $end    = Carbon::now()->addDay();
        $now    = Carbon::now();

        $result = $repo->getAgentsWithStats($start, $end, $now);
        $ids    = $result->pluck('id')->toArray();

        expect($ids)->toContain($active->id)
            ->and($ids)->not->toContain($inactive->id);
    });

    it('retorna a coluna pending como inteiro', function () {
        mr_agent(['department_id' => 1]);

        $repo   = new MonitorRepository();
        $start  = Carbon::now()->subDay();
        $end    = Carbon::now()->addDay();
        $now    = Carbon::now();

        $result = $repo->getAgentsWithStats($start, $end, $now);

        expect($result->first())->toHaveKey('pending')
            ->and($result->first()->pending)->toBeInt();
    });

    it('retorna a coluna schedules_morning como inteiro', function () {
        mr_agent(['department_id' => 1]);

        $repo   = new MonitorRepository();
        $start  = Carbon::now()->subDay();
        $end    = Carbon::now()->addDay();
        $now    = Carbon::now();

        $result = $repo->getAgentsWithStats($start, $end, $now);

        expect($result->first())->toHaveKey('schedules_morning')
            ->and($result->first()->schedules_morning)->toBeInt();
    });

    it('retorna coleção vazia quando não há agentes ativos elegíveis', function () {
        mr_agent(['active' => false, 'department_id' => 1]);
        mr_agent(['active' => true, 'department_id' => 3]);

        $repo   = new MonitorRepository();
        $start  = Carbon::now()->subDay();
        $end    = Carbon::now()->addDay();
        $now    = Carbon::now();

        $result = $repo->getAgentsWithStats($start, $end, $now);

        expect($result)->toHaveCount(0);
    });

});

// ─── Testes: getTicketTimesInPeriod ───────────────────────────────────────────

describe('MonitorRepository — getTicketTimesInPeriod', function () {

    it('retorna horários de tickets criados dentro do período', function () {
        $agent  = mr_agent();
        $now    = Carbon::now();
        $start  = $now->copy()->subHours(2);
        $end    = $now->copy()->addHours(2);

        mr_ticket($agent->id, ['created_at' => $now]);

        $repo   = new MonitorRepository();
        $times  = $repo->getTicketTimesInPeriod($start, $end);

        expect($times)->not->toBeEmpty();
    });

    it('não retorna tickets fora do período', function () {
        $agent  = mr_agent();
        $start  = Carbon::now()->subHours(2);
        $end    = Carbon::now()->subHour();

        mr_ticket($agent->id, ['created_at' => Carbon::now()]);

        $repo  = new MonitorRepository();
        $times = $repo->getTicketTimesInPeriod($start, $end);

        expect($times)->toBeEmpty();
    });

    it('retorna os horários no formato H:i:s', function () {
        $agent  = mr_agent();
        $now    = Carbon::now();

        mr_ticket($agent->id, ['created_at' => $now]);

        $repo   = new MonitorRepository();
        $times  = $repo->getTicketTimesInPeriod($now->copy()->subMinute(), $now->copy()->addMinute());

        expect($times->first())->toMatch('/^\d{2}:\d{2}:\d{2}$/');
    });

    it('retorna coleção vazia quando não há tickets no período', function () {
        $repo  = new MonitorRepository();
        $start = Carbon::now()->addDay();
        $end   = Carbon::now()->addDays(2);

        $times = $repo->getTicketTimesInPeriod($start, $end);

        expect($times)->toBeEmpty();
    });

    it('conta corretamente múltiplos tickets no período', function () {
        $agent  = mr_agent();
        $now    = Carbon::now();
        $start  = $now->copy()->subHour();
        $end    = $now->copy()->addHour();

        mr_ticket($agent->id, ['created_at' => $now]);
        mr_ticket($agent->id, ['created_at' => $now->copy()->subMinutes(30)]);

        $repo  = new MonitorRepository();
        $times = $repo->getTicketTimesInPeriod($start, $end);

        expect($times->count())->toBeGreaterThanOrEqual(2);
    });

});
