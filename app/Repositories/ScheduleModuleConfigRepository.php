<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ScheduleModuleConfigRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule\Module;
use Illuminate\Database\Eloquent\Collection;

class ScheduleModuleConfigRepository implements ScheduleModuleConfigRepositoryInterface
{
    public function getAssignedModules(Company $company): Collection
    {
        return $company->scheduleModules()->get();
    }

    public function getAllModules(): Collection
    {
        return Module::orderBy('project')->orderBy('name')->get();
    }

    public function syncModules(Company $company, array $moduleIds): void
    {
        $company->scheduleModules()->sync($moduleIds);
    }
}
