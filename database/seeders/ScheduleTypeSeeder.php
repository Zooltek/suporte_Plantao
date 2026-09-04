<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Schedule;
use App\Models\ScheduleType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ScheduleTypeSeeder extends Seeder
{
    /**
     * Tipos nativos — comportamento idêntico ao enum hardcoded anterior,
     * acrescido de "Implantação". Reexecução é idempotente.
     */
    private const TYPES = [
        ['slug' => 'ticket',      'label' => 'Ticket',                  'requires_customer' => true,  'sort_order' => 10],
        ['slug' => 'client',      'label' => 'Atendimento ao cliente',  'requires_customer' => true,  'sort_order' => 20],
        ['slug' => 'implantacao', 'label' => 'Implantação',             'requires_customer' => true,  'sort_order' => 30],
        ['slug' => 'meeting',     'label' => 'Reunião',                 'requires_customer' => false, 'sort_order' => 40],
        ['slug' => 'medical',     'label' => 'Consulta médica',         'requires_customer' => false, 'sort_order' => 50],
        ['slug' => 'vacation',    'label' => 'Férias',                  'requires_customer' => false, 'sort_order' => 60],
        ['slug' => 'internal',    'label' => 'Compromisso interno',     'requires_customer' => false, 'sort_order' => 70],
        ['slug' => 'other',       'label' => 'Outro',                   'requires_customer' => false, 'sort_order' => 80],
    ];

    public function run(): void
    {
        DB::transaction(function (): void {
            foreach (self::TYPES as $type) {
                ScheduleType::updateOrCreate(
                    ['slug' => $type['slug']],
                    [
                        'label'             => $type['label'],
                        'requires_customer' => $type['requires_customer'],
                        'is_system'         => true,
                        'is_active'         => true,
                        'sort_order'        => $type['sort_order'],
                    ],
                );
            }

            $this->backfillSchedulesRelation();
        });
    }

    /**
     * Preenche schedule.schedule_type_id a partir de schedule.kind
     * para registros históricos, garantindo zero regressão.
     */
    private function backfillSchedulesRelation(): void
    {
        $typesBySlug = ScheduleType::query()->pluck('id', 'slug');

        Schedule::query()
            ->whereNull('schedule_type_id')
            ->whereNotNull('kind')
            ->chunkById(500, function ($schedules) use ($typesBySlug): void {
                foreach ($schedules as $schedule) {
                    $typeId = $typesBySlug[$schedule->kind] ?? null;

                    if ($typeId !== null) {
                        Schedule::withoutTimestamps(fn () =>
                            Schedule::query()
                                ->whereKey($schedule->id)
                                ->update(['schedule_type_id' => $typeId])
                        );
                    }
                }
            });
    }
}
