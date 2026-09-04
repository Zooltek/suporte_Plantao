<?php

use App\Models\Category;
use App\Models\Ticket\Ticket;
use App\Services\Helpdesk\Ticketit\SlaService;
use App\Exceptions\SlaConfigurationException;
use Carbon\Carbon;


it('returns default thresholds when settings are not present', function () {
    $service = app(SlaService::class);

    $thresholds = $service->getThresholdsMatrix();

    expect($thresholds['high']['attention'])->toBe(10)
        ->and($thresholds['high']['warning'])->toBe(20)
        ->and($thresholds['high']['critical'])->toBe(30)
        ->and($thresholds['med']['attention'])->toBe(20)
        ->and($thresholds['med']['warning'])->toBe(40)
        ->and($thresholds['med']['critical'])->toBe(60)
        ->and($thresholds['low']['attention'])->toBe(30)
        ->and($thresholds['low']['warning'])->toBe(60)
        ->and($thresholds['low']['critical'])->toBe(90);
});

it('updateThresholds lança SlaConfigurationException quando slug não existe no banco', function () {
    $service = app(SlaService::class);

    // Remove os settings para simular DB sem configuração de SLA
    \Illuminate\Support\Facades\DB::table('ticketit_settings')
        ->where('slug', 'like', 'sla_%')
        ->delete();

    $payload = [
        'sla_high_attention' => 30,
        'sla_high_warning'   => 40,
        'sla_high_critical'  => 50,
        'sla_med_attention'  => 20,
        'sla_med_warning'    => 30,
        'sla_med_critical'   => 45,
        'sla_low_attention'  => 10,
        'sla_low_warning'    => 20,
        'sla_low_critical'   => 30,
    ];

    expect(fn () => $service->updateThresholds($payload))
        ->toThrow(SlaConfigurationException::class);
});

it('resolves ticket sla level based on thresholds and priority weight', function () {
    Carbon::setTestNow('2026-02-27 12:00:00');

    $service = app(SlaService::class);
    $service->updateThresholds([
        'sla_high_attention' => 5,
        'sla_high_warning' => 10,
        'sla_high_critical' => 20,
        'sla_med_attention' => 15,
        'sla_med_warning' => 30,
        'sla_med_critical' => 45,
        'sla_low_attention' => 30,
        'sla_low_warning' => 60,
        'sla_low_critical' => 90,
    ]);

    $ticket = new Ticket();
    $ticket->created_at = now()->subMinutes(25);
    $ticket->completed_at = null;
    $ticket->setRelation('category', new Category(['priority' => Category::PRIORITY_URGENT]));
    $ticket->setRelation('subCategory', new Category(['priority' => Category::PRIORITY_HIGH]));

    expect($service->calculatePriorityWeight($ticket))->toBe(8);
    expect($service->resolveSlaLevel($ticket))->toBe('critical');

    $ticket->completed_at = now();

    expect($service->resolveSlaLevel($ticket))->toBe('resolved');

    Carbon::setTestNow();
});

it('resolveSlaLevel retorna resolved quando o chamado possui completed_at', function () {
    $service = app(SlaService::class);

    $ticket = new Ticket();
    $ticket->created_at = now()->subHours(5);
    $ticket->completed_at = now();
    $ticket->setRelation('category', new Category(['priority' => Category::PRIORITY_LOW]));

    expect($service->resolveSlaLevel($ticket))->toBe('resolved');
});
