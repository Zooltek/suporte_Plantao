<?php

namespace App\Services\Admin\Tasks;

use App\Models\Company;
use App\Models\Schedule\Module as ScheduleModule;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Models\User;
use App\Services\Admin\Implantacao\ScheduleModuleConfigService;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class TaskModuleCatalogService
{
    private ?Collection $fallbackModules = null;

    /** @var array<string, array{projects: Collection, projectModuleTree: array<string, array<int, array<string, mixed>>>, moduleCollections: array<string, Collection>}> */
    private array $catalogCache = [];

    public function __construct(
        private readonly ScheduleModuleConfigService $scheduleModuleConfigService,
    ) {}

    /**
     * Catálogo canônico usado pela tela de tarefas.
     * Prioriza a configuração de implantação/RAT e só recua para o catálogo legado
     * quando não existe nenhum módulo técnico cadastrado.
     *
     * @return array{
     *     projects: Collection<int, Project>,
     *     projectModuleTree: array<string, array<int, array<string, mixed>>>,
     *     moduleCollections: array<string, Collection<int, Label>>
     * }
     */
    public function getCatalog(?int $customerId = null): array
    {
        $cacheKey = $this->catalogCacheKey($customerId);

        if (array_key_exists($cacheKey, $this->catalogCache)) {
            return $this->catalogCache[$cacheKey];
        }

        $scheduleModules = $this->getCanonicalScheduleModules($customerId);

        if ($scheduleModules->isEmpty()) {
            return $this->catalogCache[$cacheKey] = $this->getLegacyCatalog();
        }

        return $this->catalogCache[$cacheKey] = $this->buildScheduleCatalog($scheduleModules);
    }

    public function getActiveProjects(?int $customerId = null): Collection
    {
        return $this->getCatalog($customerId)['projects'];
    }

    public function getProjectModuleTree(?int $customerId = null): array
    {
        return $this->getCatalog($customerId)['projectModuleTree'];
    }

    public function projectExists(int $projectId, ?int $customerId = null): bool
    {
        return $this->getActiveProjects($customerId)
            ->contains(fn (Project $project) => (int) $project->id === $projectId);
    }

    public function projectAllowsModule(int $projectId, int $moduleId, ?int $customerId = null): bool
    {
        return $this->getModulesForProject($projectId, $customerId)
            ->contains(fn (Label $module) => (int) $module->id === $moduleId);
    }

    public function submoduleBelongsToModule(int $moduleId, int $submoduleId): bool
    {
        return Label::query()
            ->active()
            ->whereKey($submoduleId)
            ->where('parent_id', $moduleId)
            ->exists();
    }

    public function getModulesForProject(int $projectId, ?int $customerId = null): Collection
    {
        $catalog = $this->getCatalog($customerId);

        return $catalog['moduleCollections'][(string) $projectId] ?? new Collection;
    }

    private function getCanonicalScheduleModules(?int $customerId = null): Collection
    {
        if ($customerId) {
            $company = Company::query()->find($customerId);

            if ($company) {
                return $this->scheduleModuleConfigService->getForCompany($company);
            }
        }

        return ScheduleModule::query()
            ->orderByRaw("COALESCE(project, 'Geral')")
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, ScheduleModule>  $scheduleModules
     * @return array{
     *     projects: Collection<int, Project>,
     *     projectModuleTree: array<string, array<int, array<string, mixed>>>,
     *     moduleCollections: array<string, Collection<int, Label>>
     * }
     */
    private function buildScheduleCatalog(Collection $scheduleModules): array
    {
        $projects = new Collection;
        $projectModuleTree = [];
        $moduleCollections = [];

        $groupedByProject = $scheduleModules
            ->groupBy(fn (ScheduleModule $module) => $this->normalizeProjectName($module->project));

        foreach ($groupedByProject as $projectName => $projectModules) {
            $project = $this->syncProjectFromSchedule($projectName);
            $rootLabels = $projectModules
                ->map(fn (ScheduleModule $module) => $this->syncRootLabelFromSchedule($module))
                ->values();

            $project->modules()->sync($rootLabels->pluck('id')->all());

            $project = $this->loadProjectModules($project);

            $projects->push($project);
            $moduleCollections[(string) $project->id] = $project->modules;
            $projectModuleTree[(string) $project->id] = $this->serializeModules($project->modules);
        }

        return [
            'projects' => $projects->sortBy('name')->values(),
            'projectModuleTree' => $projectModuleTree,
            'moduleCollections' => $moduleCollections,
        ];
    }

    /**
     * @return array{
     *     projects: Collection<int, Project>,
     *     projectModuleTree: array<string, array<int, array<string, mixed>>>,
     *     moduleCollections: array<string, Collection<int, Label>>
     * }
     */
    private function getLegacyCatalog(): array
    {
        $projects = Project::query()
            ->active()
            ->with([
                'modules' => fn ($query) => $this->applyRootModuleQuery($query),
            ])
            ->orderBy('name')
            ->get(['id', 'name', 'schedule_project_name']);

        $fallbackModules = $this->getFallbackModules();
        $projectModuleTree = [];
        $moduleCollections = [];

        foreach ($projects as $project) {
            $modules = $project->modules->isNotEmpty()
                ? $project->modules
                : $fallbackModules;

            $moduleCollections[(string) $project->id] = $modules;
            $projectModuleTree[(string) $project->id] = $this->serializeModules($modules);
        }

        return [
            'projects' => $projects,
            'projectModuleTree' => $projectModuleTree,
            'moduleCollections' => $moduleCollections,
        ];
    }

    private function getFallbackModules(): Collection
    {
        if ($this->fallbackModules !== null) {
            return $this->fallbackModules;
        }

        return $this->fallbackModules = Label::query()
            ->active()
            ->whereNull('parent_id')
            ->with(['childs' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    private function syncProjectFromSchedule(string $projectName): Project
    {
        $project = Project::query()
            ->where('schedule_project_name', $projectName)
            ->first();

        if (! $project) {
            $project = Project::query()
                ->whereNull('schedule_project_name')
                ->where('name', $projectName)
                ->first();
        }

        if ($project) {
            $project->fill([
                'name' => $projectName,
                'schedule_project_name' => $projectName,
                'status' => 1,
            ]);

            if (blank($project->color)) {
                $project->color = '#1d4ed8';
            }

            if (! $project->user_id) {
                $project->user_id = $this->resolveCatalogOwnerId();
            }

            $project->save();

            return $project;
        }

        return Project::query()->create([
            'name' => $projectName,
            'schedule_project_name' => $projectName,
            'color' => '#1d4ed8',
            'status' => 1,
            'user_id' => $this->resolveCatalogOwnerId(),
        ]);
    }

    private function syncRootLabelFromSchedule(ScheduleModule $scheduleModule): Label
    {
        $moduleName = $this->normalizeModuleName($scheduleModule->name);

        $label = Label::query()
            ->where('schedule_module_id', $scheduleModule->id)
            ->first();

        if (! $label) {
            $label = Label::query()
                ->whereNull('parent_id')
                ->whereNull('schedule_module_id')
                ->where('name', $moduleName)
                ->orderBy('id')
                ->first();
        }

        if ($label) {
            $label->fill([
                'name' => $moduleName,
                'is_active' => true,
                'parent_id' => null,
                'schedule_module_id' => $scheduleModule->id,
            ]);

            if (! $label->user_id) {
                $label->user_id = $this->resolveCatalogOwnerId();
            }

            $label->save();

            return $label;
        }

        return Label::query()->create([
            'name' => $moduleName,
            'user_id' => $this->resolveCatalogOwnerId(),
            'is_active' => true,
            'parent_id' => null,
            'schedule_module_id' => $scheduleModule->id,
        ]);
    }

    private function loadProjectModules(Project $project): Project
    {
        return $project->load([
            'modules' => fn ($query) => $this->applyRootModuleQuery($query),
        ]);
    }

    private function applyRootModuleQuery($query): void
    {
        $query->active()
            ->whereNull('labels.parent_id')
            ->with(['childs' => fn ($childQuery) => $childQuery->active()->orderBy('name')])
            ->orderBy('labels.name');
    }

    private function normalizeProjectName(?string $projectName): string
    {
        $projectName = trim((string) $projectName);

        return $projectName !== '' ? $projectName : 'Geral';
    }

    private function normalizeModuleName(?string $moduleName): string
    {
        $moduleName = trim((string) $moduleName);

        return $moduleName !== '' ? $moduleName : 'Módulo sem nome';
    }

    private function resolveCatalogOwnerId(): int
    {
        $userId = auth('admin')->id() ?? auth()->id() ?? User::query()->value('id');

        if (! $userId) {
            throw new RuntimeException('Nenhum usuário disponível para sincronizar o catálogo de tarefas.');
        }

        return (int) $userId;
    }

    private function catalogCacheKey(?int $customerId): string
    {
        return $customerId ? "customer:{$customerId}" : 'global';
    }

    /**
     * @param  Collection<int, Label>  $modules
     * @return array<int, array{id: string, name: string, scheduleModuleId: string, childs: array<int, array{id: string, name: string}>}>
     */
    private function serializeModules(Collection $modules): array
    {
        return $modules->map(fn (Label $module) => [
            'id' => (string) $module->id,
            'name' => $module->name,
            'scheduleModuleId' => (string) ($module->schedule_module_id ?? ''),
            'childs' => $module->childs
                ->map(fn (Label $child) => [
                    'id' => (string) $child->id,
                    'name' => $child->name,
                ])
                ->values()
                ->all(),
        ])->values()->all();
    }
}
