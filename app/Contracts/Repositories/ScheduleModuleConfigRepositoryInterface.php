<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Company;
use App\Models\Schedule\Module;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

interface ScheduleModuleConfigRepositoryInterface
{
    /**
     * Retorna os módulos associados ao cliente.
     *
     * @return Collection<int, Module>
     */
    public function getAssignedModules(Company $company): Collection;

    /**
     * Retorna todos os Modules ordenados por projeto e nome.
     *
     * @return Collection<int, Module>
     */
    public function getAllModules(): Collection;

    /**
     * Sincroniza (substitui integralmente) os módulos de um cliente.
     *
     * @param  int[]  $moduleIds
     */
    public function syncModules(Company $company, array $moduleIds): void;
}
