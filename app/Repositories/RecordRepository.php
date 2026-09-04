<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\RecordRepositoryInterface;
use App\Models\Schedule;
use App\Models\Schedule\Element;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Record;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RecordRepository implements RecordRepositoryInterface
{
    /**
     * Persiste um novo Record com seus elementos dinâmicos dentro de uma transação DB.
     * Eager loading das relações schedule.customer e customer é feito no findActiveForPrint.
     */
    public function createWithElements(Schedule $schedule, array $validated, array $rawInput): Record
    {
        return DB::transaction(function () use ($schedule, $validated, $rawInput) {
            $date = Carbon::parse($validated['date']);

            $record                = new Record();
            $record->schedule_id   = $schedule->id;
            $record->customer_id   = $schedule->customer_id ?? data_get($validated, 'customer_id');
            $record->module_id     = $validated['module_id'];
            $record->start         = $date->copy()->setTimeFrom(Carbon::parse($validated['start_hour']));
            $record->end           = $date->copy()->setTimeFrom(Carbon::parse($validated['end_hour']));

            if (!empty($validated['interval_start']) && !empty($validated['interval_end'])) {
                $record->interval_start = $date->copy()->setTimeFrom(Carbon::parse($validated['interval_start']));
                $record->interval_end   = $date->copy()->setTimeFrom(Carbon::parse($validated['interval_end']));
            }

            $record->agent_id = $validated['agent_id'];
            $record->contact  = data_get($validated, 'contact');
            $record->obs      = data_get($validated, 'obs');
            $record->version  = data_get($validated, 'version');
            $record->release  = data_get($validated, 'release');
            $record->resolved = filter_var(
                data_get($validated, 'resolved'),
                FILTER_VALIDATE_BOOLEAN,
                FILTER_NULL_ON_FAILURE
            );
            $record->save();

            $this->syncElements($record, $rawInput);

            return $record;
        });
    }

    /**
     * Busca um Record ativo pelo ID com eager loading das relações de impressão.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function findActiveForPrint(int $recordId): Record
    {
        return Record::active()
            ->with(['schedule.customer', 'customer'])
            ->findOrFail($recordId);
    }

    /**
     * Retorna todos os ElementTypes (com grupo) associados ao módulo.
     */
    public function getElementTypesByModule(int $moduleId): Collection
    {
        return ElementType::with('group')
            ->where('module_id', $moduleId)
            ->get();
    }

    /**
     * Retorna os Elements de um Record, indexados pelo element_id (keyBy).
     */
    public function getRecordElementsKeyed(int $recordId): Collection
    {
        return Element::where('record_id', $recordId)
            ->get()
            ->keyBy('element_id');
    }

    /**
     * Insere em lote os elementos dinâmicos baseados nos ElementTypes do módulo.
     */
    private function syncElements(Record $record, array $rawInput): void
    {
        $types = ElementType::where('module_id', $record->module_id)->get();

        $elementsToInsert = [];

        foreach ($types as $type) {
            if (!empty($rawInput[$type->name])) {
                $elementsToInsert[] = [
                    'element_id' => $type->id,
                    'value'      => $rawInput[$type->name],
                ];
            }
        }

        if (count($elementsToInsert) > 0) {
            $record->elements()->createMany($elementsToInsert);
        }
    }
}
