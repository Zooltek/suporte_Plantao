<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ScheduleElementRepositoryInterface;
use App\Models\CompanyModuleType;
use App\Models\Schedule\ElementType;
use Illuminate\Database\Eloquent\Collection;

class ScheduleElementRepository implements ScheduleElementRepositoryInterface
{
    /**
     * Retorna os ElementTypes filtrados por rat_module_id (schedule_record_module.id).
     */
    public function getAllWithGroup(?int $ratModuleId = null): Collection
    {
        return ElementType::with('group')
            ->orderBy('name', 'asc')
            ->when($ratModuleId, fn ($query, $id) => $query->where('module_id', $id))
            ->get();
    }

    /**
     * Resolve o rat_module_id a partir de um company_module_types.id.
     * Esta ponte elimina o ID space collision: o formulário do ticket passa
     * CompanyModuleType.id; o Repository converte para schedule_record_module.id.
     */
    public function resolveRatModuleId(int $companyModuleTypeId): ?int
    {
        return CompanyModuleType::where('id', $companyModuleTypeId)
            ->value('rat_module_id');
    }
}
