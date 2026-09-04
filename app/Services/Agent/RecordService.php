<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\RecordRepositoryInterface;
use App\Jobs\Agent\Schedule\SyncRecord;
use App\Models\Schedule;
use App\Models\Schedule\Record;

class RecordService
{
    public function __construct(
        private readonly RecordRepositoryInterface $repository
    ) {}

    /**
     * Cria um novo registro (Record) e seus elementos dinâmicos via Repository.
     * Despacha o Job de sincronização após a persistência.
     */
    public function createRecord(Schedule $schedule, array $validated, array $rawInput): Record
    {
        $record = $this->repository->createWithElements($schedule, $validated, $rawInput);

        SyncRecord::dispatch($record)->delay(now()->addSeconds(2));

        return $record;
    }

    /**
     * Monta a estrutura complexa de dados para a tela de impressão.
     * Toda leitura de banco é delegada ao Repository.
     */
    public function getPrintData(int $recordId): array
    {
        $record   = $this->repository->findActiveForPrint($recordId);
        $customer = $record->schedule->customer ?? $record->customer;

        $elementTypes   = $this->repository->getElementTypesByModule($record->module_id);
        $recordElements = $this->repository->getRecordElementsKeyed($record->id);

        $elements = $elementTypes
            ->groupBy(fn ($type) => $type->group?->name ?? 'Sem Grupo')
            ->map(fn ($group) => $group->mapWithKeys(fn ($type) => [
                $type->id => [
                    'label' => $type->label,
                    'value' => $recordElements->get($type->id)?->value ?? 0,
                    'type'  => $type->type,
                ],
            ]));

        return compact('record', 'customer', 'elements');
    }
}
