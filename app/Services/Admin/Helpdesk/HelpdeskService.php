<?php

namespace App\Services\Admin\Helpdesk;

use App\Contracts\Repositories\HelpdeskRepositoryInterface;
use App\Models\CompanyModuleType;
use App\Models\Schedule\Module as RatModule;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Status;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * HelpdeskService — Regras de negócio do painel admin do helpdesk.
 *
 * Toda lógica de acesso a dados foi extraída para HelpdeskRepository.
 * O Service garante: defaults de cor, validações de domínio (DomainException),
 * geração de slug, orquestração de invalidação de cache.
 */
class HelpdeskService
{
    public function __construct(
        private readonly HelpdeskRepositoryInterface $helpdeskRepository,
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────

    public function getDashboardMetrics(): array
    {
        return $this->helpdeskRepository->getDashboardMetrics();
    }

    // ── Status ─────────────────────────────────────────────────────────────

    public function getAllStatuses(): Collection
    {
        return $this->helpdeskRepository->getAllStatuses();
    }

    public function findStatus(int $id): Status
    {
        return $this->helpdeskRepository->findStatusOrFail($id);
    }

    public function createStatus(array $data): Status
    {
        $status = $this->helpdeskRepository->createStatus([
            'name'  => $data['name'],
            'color' => $data['color'] ?? '#6366f1',
        ]);

        $this->helpdeskRepository->forgetDashboardCache();

        return $status;
    }

    public function updateStatus(int $id, array $data): Status
    {
        $status = $this->helpdeskRepository->findStatusOrFail($id);

        $this->helpdeskRepository->updateStatus($status, [
            'name'  => $data['name'],
            'color' => $data['color'] ?? $status->color,
        ]);

        $this->helpdeskRepository->forgetDashboardCache();

        return $status->refresh();
    }

    public function deleteStatus(int $id): bool
    {
        $status = $this->helpdeskRepository->findStatusOrFail($id);

        // Regra de domínio: status com tickets vinculados não pode ser removido
        if ($this->helpdeskRepository->statusHasTickets($status)) {
            throw new \DomainException('Não é possível remover um status que possui chamados vinculados.');
        }

        $this->helpdeskRepository->forgetDashboardCache();

        return $this->helpdeskRepository->deleteStatus($status);
    }

    // ── Priority ───────────────────────────────────────────────────────────

    public function getAllPriorities(): Collection
    {
        return $this->helpdeskRepository->getAllPriorities();
    }

    public function findPriority(int $id): Priority
    {
        return $this->helpdeskRepository->findPriorityOrFail($id);
    }

    public function createPriority(array $data): Priority
    {
        $priority = $this->helpdeskRepository->createPriority([
            'name'  => $data['name'],
            'color' => $data['color'] ?? '#6366f1',
        ]);

        $this->helpdeskRepository->forgetDashboardCache();

        return $priority;
    }

    public function updatePriority(int $id, array $data): Priority
    {
        $priority = $this->helpdeskRepository->findPriorityOrFail($id);

        $this->helpdeskRepository->updatePriority($priority, [
            'name'  => $data['name'],
            'color' => $data['color'] ?? $priority->color,
        ]);

        $this->helpdeskRepository->forgetDashboardCache();

        return $priority->refresh();
    }

    public function deletePriority(int $id): bool
    {
        $priority = $this->helpdeskRepository->findPriorityOrFail($id);

        // Regra de domínio: prioridade com tickets não pode ser removida
        if ($this->helpdeskRepository->priorityHasTickets($priority)) {
            throw new \DomainException('Não é possível deletar uma prioridade vinculada a chamados.');
        }

        $this->helpdeskRepository->forgetDashboardCache();

        return $this->helpdeskRepository->deletePriority($priority);
    }

    // ── Origin ─────────────────────────────────────────────────────────────

    public function getAllOrigins(): Collection
    {
        return $this->helpdeskRepository->getAllOrigins();
    }

    public function findOrigin(int $id): Origin
    {
        return $this->helpdeskRepository->findOriginOrFail($id);
    }

    public function createOrigin(array $data): Origin
    {
        return $this->helpdeskRepository->createOrigin([
            'name'        => $data['name'],
            'description' => $data['description'] ?? null,
            'status'      => $data['status'] ?? 1,
        ]);
    }

    public function updateOrigin(int $id, array $data): Origin
    {
        $origin = $this->helpdeskRepository->findOriginOrFail($id);

        $this->helpdeskRepository->updateOrigin($origin, [
            'name'        => $data['name'],
            'description' => $data['description'] ?? $origin->description,
            'status'      => $data['status'] ?? $origin->status,
        ]);

        return $origin->refresh();
    }

    public function deleteOrigin(int $id): bool
    {
        $origin = $this->helpdeskRepository->findOriginOrFail($id);

        // Regra de domínio: origem com tickets não pode ser removida
        if ($this->helpdeskRepository->originHasTickets($origin)) {
            throw new \DomainException('Não é possível remover uma origem vinculada a chamados.');
        }

        return $this->helpdeskRepository->deleteOrigin($origin);
    }

    // ── CompanyModuleType ──────────────────────────────────────────────────

    public function getAllModuleTypes(): Collection
    {
        return $this->helpdeskRepository->getAllModuleTypes();
    }

    public function findModuleType(int $id): CompanyModuleType
    {
        return $this->helpdeskRepository->findModuleTypeOrFail($id);
    }

    public function getAllRatModules(): Collection
    {
        return $this->helpdeskRepository->getAllRatModules();
    }

    public function createModuleType(array $data): CompanyModuleType
    {
        // Regra de negócio: slug gerado automaticamente a partir do nome
        return $this->helpdeskRepository->createModuleType([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name'], '_'),
            'is_active'     => !empty($data['is_active']),
            'sort_order'    => (int) ($data['sort_order'] ?? 0),
            'rat_module_id' => !empty($data['rat_module_id']) ? (int) $data['rat_module_id'] : null,
        ]);
    }

    public function updateModuleType(int $id, array $data): CompanyModuleType
    {
        $module = $this->helpdeskRepository->findModuleTypeOrFail($id);

        $this->helpdeskRepository->updateModuleType($module, [
            'name'          => $data['name'],
            'is_active'     => !empty($data['is_active']),
            'sort_order'    => (int) ($data['sort_order'] ?? $module->sort_order),
            'rat_module_id' => !empty($data['rat_module_id']) ? (int) $data['rat_module_id'] : null,
        ]);

        return $module->refresh();
    }

    public function deleteModuleType(int $id): bool
    {
        $module = $this->helpdeskRepository->findModuleTypeOrFail($id);

        return $this->helpdeskRepository->deleteModuleType($module);
    }

    // ── Settings ───────────────────────────────────────────────────────────

    public function getSettingsSections(): array
    {
        return $this->helpdeskRepository->getSettingsSections();
    }

    public function updateSetting(string $slug, mixed $value): void
    {
        $setting = $this->helpdeskRepository->findOrNewSetting($slug);
        $setting->value = $value;

        // Regra de negócio: primeira criação define o valor como padrão
        if (!$setting->exists) {
            $setting->default = $value;
        }

        $this->helpdeskRepository->saveSetting($setting);

        // Invalida os dois caches afetados por mudanças de configuração
        $this->helpdeskRepository->forgetSettingsCache();
        $this->helpdeskRepository->forgetDashboardCache();
    }
}
