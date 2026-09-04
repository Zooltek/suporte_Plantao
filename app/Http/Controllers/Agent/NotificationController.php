<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Schedule\Record;
use App\Jobs\Agent\Schedule\SyncSchedule;
use App\Jobs\Agent\Schedule\SyncRecord;
use Illuminate\Support\Carbon;

class NotificationController extends Controller
{
    /**
     * Despacha o Job para sincronização de agendamento.
     */
    public function syncSchedule(Schedule $schedule): bool
    {
        SyncSchedule::dispatch($schedule)
            ->delay(Carbon::now()->addSeconds(2));

        return true;
    }

    /**
     * Despacha o Job para sincronização de registro.
     */
    public function syncRecord(Record $record): bool
    {
        SyncRecord::dispatch($record)
            ->delay(Carbon::now()->addSeconds(2));

        return true;
    }
}
