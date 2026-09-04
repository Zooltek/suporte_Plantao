<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

interface ScheduleElementRepositoryInterface
{
    /**
     * Retorna os ElementTypes do Schedule opcionalmente filtrados por módulo RAT
     * (schedule_record_module.id), com eager loading do grupo, ordenados por nome.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Schedule\ElementType>
     */
    public function getAllWithGroup(?int $ratModuleId = null): \Illuminate\Database\Eloquent\Collection;

    /**
     * Resolve o rat_module_id a partir de um CompanyModuleType.id.
     * Usado quando o chamador informa module_context=ticket.
     * Retorna null quando o módulo não existe ou não tem template RAT vinculado.
     */
    public function resolveRatModuleId(int $companyModuleTypeId): ?int;
}
