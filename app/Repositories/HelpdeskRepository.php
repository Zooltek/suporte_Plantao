<?php

namespace App\Repositories;

use App\Contracts\Repositories\HelpdeskRepositoryInterface;
use App\Models\CompanyModuleType;
use App\Models\Schedule\Module as RatModule;
use App\Models\Ticket\Origin;
use App\Models\Ticket\Priority;
use App\Models\Ticket\Setting;
use App\Models\Ticket\Status;
use App\Models\Ticket\Ticket;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HelpdeskRepository implements HelpdeskRepositoryInterface
{
    private const DASHBOARD_CACHE_KEY = 'helpdesk.admin.dashboard_metrics';

    private const DASHBOARD_CACHE_TTL = 300;

    private const SETTINGS_CACHE_KEY = 'ticketit_settings';

    // ── Dashboard ──────────────────────────────────────────────────────────

    public function getDashboardMetrics(): array
    {
        return Cache::remember(self::DASHBOARD_CACHE_KEY, self::DASHBOARD_CACHE_TTL, function () {
            $tickets = Ticket::query();
            $terminalIds = Status::where('is_terminal', true)->pluck('id');

            $total = (clone $tickets)->count();
            $open = (clone $tickets)->whereNotIn('status_id', $terminalIds)->count();
            $closed = (clone $tickets)->whereIn('status_id', $terminalIds)->count();
            $today = (clone $tickets)->whereDate('created_at', today())->count();

            $byStatus = Status::withCount('tickets')
                ->orderBy('name')
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'name' => $s->name,
                    'color' => $s->color,
                    'count' => $s->tickets_count,
                ]);

            $topCategories = Ticket::select('category_id', DB::raw('count(*) as total'))
                ->with('category')
                ->whereNotNull('category_id')
                ->groupBy('category_id')
                ->orderByDesc('total')
                ->limit(5)
                ->get()
                ->map(fn ($t) => [
                    'name' => $t->category?->header ?? $t->category?->name ?? '—',
                    'total' => $t->total,
                ]);

            return compact('total', 'open', 'closed', 'today', 'byStatus', 'topCategories');
        });
    }

    public function forgetDashboardCache(): void
    {
        Cache::forget(self::DASHBOARD_CACHE_KEY);
    }

    // ── Status ─────────────────────────────────────────────────────────────

    public function getAllStatuses(): Collection
    {
        return Status::withCount('tickets')->orderBy('name')->get();
    }

    public function findStatusOrFail(int $id): Status
    {
        return Status::findOrFail($id);
    }

    public function createStatus(array $attributes): Status
    {
        return Status::create($attributes);
    }

    public function updateStatus(Status $status, array $attributes): void
    {
        $status->update($attributes);
    }

    public function deleteStatus(Status $status): bool
    {
        return (bool) $status->delete();
    }

    public function statusHasTickets(Status $status): bool
    {
        return $status->tickets()->exists();
    }

    // ── Priority ───────────────────────────────────────────────────────────

    public function getAllPriorities(): Collection
    {
        return Priority::withCount('tickets')->orderBy('name')->get();
    }

    public function findPriorityOrFail(int $id): Priority
    {
        return Priority::findOrFail($id);
    }

    public function createPriority(array $attributes): Priority
    {
        return Priority::create($attributes);
    }

    public function updatePriority(Priority $priority, array $attributes): void
    {
        $priority->update($attributes);
    }

    public function deletePriority(Priority $priority): bool
    {
        return (bool) $priority->delete();
    }

    public function priorityHasTickets(Priority $priority): bool
    {
        return method_exists($priority, 'tickets') && $priority->tickets()->exists();
    }

    // ── Origin ─────────────────────────────────────────────────────────────

    public function getAllOrigins(): Collection
    {
        return Origin::withCount('tickets')->orderBy('name')->get();
    }

    public function findOriginOrFail(int $id): Origin
    {
        return Origin::findOrFail($id);
    }

    public function createOrigin(array $attributes): Origin
    {
        return Origin::create($attributes);
    }

    public function updateOrigin(Origin $origin, array $attributes): void
    {
        $origin->update($attributes);
    }

    public function deleteOrigin(Origin $origin): bool
    {
        return (bool) $origin->delete();
    }

    public function originHasTickets(Origin $origin): bool
    {
        return $origin->tickets()->exists();
    }

    // ── CompanyModuleType ──────────────────────────────────────────────────

    public function getAllModuleTypes(): Collection
    {
        return CompanyModuleType::with([
            'ratModule' => fn ($query) => $query->withCount('elementTypes'),
        ])
            ->withCount('companies')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function getAllRatModules(): Collection
    {
        return RatModule::withCount('elementTypes')
            ->orderBy('project')
            ->orderBy('name')
            ->get();
    }

    public function findModuleTypeOrFail(int $id): CompanyModuleType
    {
        return CompanyModuleType::with([
            'ratModule' => fn ($query) => $query->withCount('elementTypes'),
        ])
            ->withCount('companies')
            ->findOrFail($id);
    }

    public function createModuleType(array $attributes): CompanyModuleType
    {
        return CompanyModuleType::create($attributes);
    }

    public function updateModuleType(CompanyModuleType $module, array $attributes): void
    {
        $module->update($attributes);
    }

    public function deleteModuleType(CompanyModuleType $module): bool
    {
        $module->companies()->detach();

        return (bool) $module->delete();
    }

    // ── Settings ───────────────────────────────────────────────────────────

    public function getSettingsSections(): array
    {
        $statuses = Status::orderBy('name')->pluck('name', 'id');
        $priorities = Priority::orderBy('name')->pluck('name', 'id');

        return [
            'statuses' => $statuses,
            'priorities' => $priorities,
            'settings' => Setting::all()->keyBy('slug'),
        ];
    }

    public function findOrNewSetting(string $slug): Setting
    {
        return Setting::firstOrNew(['slug' => $slug]);
    }

    public function saveSetting(Setting $setting): void
    {
        $setting->save();
    }

    public function forgetSettingsCache(): void
    {
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }
}
