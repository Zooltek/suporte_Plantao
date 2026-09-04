<?php

namespace App\Contracts\Repositories;

use App\Models\CompanyModuleType;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Setting;
use App\Models\Ticket\Status;
use Illuminate\Support\Collection;

interface HelpdeskRepositoryInterface
{
    // ── Dashboard ──────────────────────────────────────────────────────────

    /**
     * Retorna os totalizadores do painel admin (total, open, closed, today,
     * byStatus, topCategories). Resultado armazenado em cache por 5 min.
     */
    public function getDashboardMetrics(): array;

    /**
     * Invalida o cache do dashboard (chamado após qualquer mutação relevante).
     */
    public function forgetDashboardCache(): void;

    // ── Status ─────────────────────────────────────────────────────────────

    public function getAllStatuses(): Collection;

    public function findStatusOrFail(int $id): Status;

    /** @param  array{name: string, color: string}  $attributes */
    public function createStatus(array $attributes): Status;

    /** @param  array{name: string, color: string}  $attributes */
    public function updateStatus(Status $status, array $attributes): void;

    public function deleteStatus(Status $status): bool;

    public function statusHasTickets(Status $status): bool;

    // ── Priority ───────────────────────────────────────────────────────────

    public function getAllPriorities(): Collection;

    public function findPriorityOrFail(int $id): Priority;

    /** @param  array{name: string, color: string}  $attributes */
    public function createPriority(array $attributes): Priority;

    /** @param  array{name: string, color: string}  $attributes */
    public function updatePriority(Priority $priority, array $attributes): void;

    public function deletePriority(Priority $priority): bool;

    public function priorityHasTickets(Priority $priority): bool;

    // ── Origin ─────────────────────────────────────────────────────────────

    public function getAllOrigins(): Collection;

    public function findOriginOrFail(int $id): Origin;

    /** @param  array{name: string, description?: ?string, status?: int}  $attributes */
    public function createOrigin(array $attributes): Origin;

    public function updateOrigin(Origin $origin, array $attributes): void;

    public function deleteOrigin(Origin $origin): bool;

    public function originHasTickets(Origin $origin): bool;

    // ── CompanyModuleType ──────────────────────────────────────────────────

    /** Retorna todos os CompanyModuleTypes com ratModule eager-loaded. */
    public function getAllModuleTypes(): Collection;

    public function findModuleTypeOrFail(int $id): CompanyModuleType;

    /** @param  array{name: string, slug: string, is_active: bool, sort_order: int, rat_module_id: ?int}  $attributes */
    public function createModuleType(array $attributes): CompanyModuleType;

    /** @param  array{name?: string, is_active?: bool, sort_order?: int, rat_module_id?: ?int}  $attributes */
    public function updateModuleType(CompanyModuleType $module, array $attributes): void;

    public function deleteModuleType(CompanyModuleType $module): bool;

    /**
     * Retorna todos os módulos RAT (schedule_record_module) para popular
     * o select de vínculo no formulário admin de CompanyModuleType.
     */
    public function getAllRatModules(): Collection;

    // ── Settings ───────────────────────────────────────────────────────────

    /**
     * Retorna os dados necessários para a tela de configurações:
     * statuses (id→name), priorities (id→name), settings (keyBy slug).
     */
    public function getSettingsSections(): array;

    public function findOrNewSetting(string $slug): Setting;

    public function saveSetting(Setting $setting): void;

    /** Invalida o cache de configurações do ticketit. */
    public function forgetSettingsCache(): void;
}
