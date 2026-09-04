<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\ScheduleElementRepositoryInterface;
use Illuminate\Support\Collection;

class ElementService
{
    public const CONTEXT_SCHEDULE = 'schedule';

    public const CONTEXT_TICKET = 'ticket';

    public function __construct(
        private readonly ScheduleElementRepositoryInterface $elementRepository,
    ) {}

    /**
     * Retorna os elementos RAT agrupados pelo nome do grupo.
     *
     * Contextos suportados:
     * - schedule: module_id já é schedule_record_module.id
     * - ticket: module_id é company_module_types.id e precisa ser resolvido para rat_module_id
     *
     * Módulos de ticket sem template RAT vinculado retornam coleção vazia.
     */
    public function getGroupedElements(?int $moduleId = null, string $context = self::CONTEXT_SCHEDULE): Collection
    {
        if ($moduleId === null) {
            $elements = $this->elementRepository->getAllWithGroup(null);

            return $elements->groupBy(fn ($element) => $element->group?->name ?? 'Sem Grupo');
        }

        $ratModuleId = $context === self::CONTEXT_TICKET
            ? $this->elementRepository->resolveRatModuleId($moduleId)
            : $moduleId;

        if ($ratModuleId === null) {
            return collect();
        }

        $elements = $this->elementRepository->getAllWithGroup($ratModuleId);

        return $elements->groupBy(fn ($element) => $element->group?->name ?? 'Sem Grupo');
    }
}
