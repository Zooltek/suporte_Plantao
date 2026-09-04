<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Schedule;
use App\Models\Schedule\Element;
use App\Models\Schedule\ElementType;
use App\Models\Schedule\Record;
use Illuminate\Database\Eloquent\Collection;

interface RecordRepositoryInterface
{
    /**
     * Persiste um novo Record dentro de uma transação,
     * incluindo seus elementos dinâmicos.
     */
    public function createWithElements(Schedule $schedule, array $validated, array $rawInput): Record;

    /**
     * Busca um Record ativo com suas relações para impressão.
     */
    public function findActiveForPrint(int $recordId): Record;

    /**
     * Retorna todos os ElementTypes (com grupo) do módulo informado.
     */
    public function getElementTypesByModule(int $moduleId): Collection;

    /**
     * Retorna os Elements de um Record, indexados por element_id.
     */
    public function getRecordElementsKeyed(int $recordId): Collection;
}
