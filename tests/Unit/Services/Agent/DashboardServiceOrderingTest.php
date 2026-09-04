<?php

use App\Models\Schedule;
use App\Models\Ticket\Attendance;
use App\Models\Ticket\Ticket;
use App\Services\Agent\DashboardService;
use Illuminate\Support\Carbon;

describe('DashboardService — ordenação', function () {

    it('getRecentAttendances retorna atendimentos do mais antigo para o mais recente', function () {
        $ticket = Ticket::factory()->create();

        $first  = Attendance::factory()->create(['ticket_id' => $ticket->id, 'created_at' => now()->subDays(3)]);
        $second = Attendance::factory()->create(['ticket_id' => $ticket->id, 'created_at' => now()->subDays(2)]);
        $third  = Attendance::factory()->create(['ticket_id' => $ticket->id, 'created_at' => now()->subDay()]);

        $user        = actingAsAdmin();
        $service     = app(DashboardService::class);
        $attendances = $service->getCondensedData($user, [])['attendances'];

        $ids = $attendances->pluck('id')->values();

        expect($ids->search($first->id))->toBeLessThan($ids->search($second->id))
            ->and($ids->search($second->id))->toBeLessThan($ids->search($third->id));
    });

    it('buildSchedulesCalendar ordena agendamentos confirmados antes dos agendados no mesmo dia', function () {
        $user = actingAsAdmin();
        $day  = Carbon::now()->startOfWeek(Carbon::MONDAY); // Segunda-feira da semana atual (ISO)

        $scheduled  = Schedule::factory()->create([
            'status'  => 'sch',
            'start_at' => $day->copy()->setTime(9, 0),
        ]);
        $confirmed  = Schedule::factory()->create([
            'status'  => 'con',
            'start_at' => $day->copy()->setTime(10, 0), // hora DEPOIS mas peso maior
        ]);

        $service = app(DashboardService::class);
        $data    = $service->getCondensedData($user, ['start' => $day->format('Y-m-d')])['schedules_data'];

        $dateKey  = $day->format('d-m-Y');
        $morning  = collect($data[$dateKey]['morning'])->filter()->values();

        // Confirmado deve aparecer antes do agendado mesmo sendo mais tardio no horário
        $confirmedPos  = $morning->search(fn ($s) => $s->id === $confirmed->id);
        $scheduledPos  = $morning->search(fn ($s) => $s->id === $scheduled->id);

        expect($confirmedPos)->toBeLessThan($scheduledPos);
    });

});
