<?php

declare(strict_types=1);

namespace App\Services\Admin\Implantacao;

use App\Contracts\Repositories\ScheduleModuleConfigRepositoryInterface;
use App\Models\Company;
use App\Models\Schedule\Module;
use Illuminate\Support\Collection;

/**
 * Gerencia quais módulos técnicos de implantação estão disponíveis para cada cliente.
 *
 * Regra de negócio: se nenhum módulo estiver configurado para o cliente,
 * todos os módulos são exibidos (fallback aberto — preserva comportamento original).
 */
class ScheduleModuleConfigService
{
    public function __construct(
        private readonly ScheduleModuleConfigRepositoryInterface $repository,
    ) {}

    /**
     * Retorna os módulos disponíveis para o cliente.
     * Fallback: todos os módulos, se nenhum configurado.
     */
    public function getForCompany(Company $company): Collection
    {
        $assigned = $this->repository->getAssignedModules($company);

        return $assigned->isEmpty()
            ? $this->repository->getAllModules()
            : $assigned;
    }

    /**
     * Persiste a seleção de módulos para um cliente (substitui integralmente).
     *
     * @param  int[]  $moduleIds
     */
    public function syncForCompany(Company $company, array $moduleIds): void
    {
        $this->repository->syncModules($company, $moduleIds);
    }

    /**
     * Todos os módulos agrupados por projeto — usados no form do admin.
     */
    public function getAllGrouped(): Collection
    {
        return $this->repository->getAllModules()
            ->groupBy(fn (Module $m) => $m->project ?? 'Geral');
    }
}
