<?php

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Services\Admin\Tasks\TaskModuleCatalogService;

describe('TaskModuleCatalogService', function () {

    it('serializa a árvore de módulos e submódulos por projeto', function () {
        $project = Project::factory()->create(['name' => 'EasyControl']);
        $module = Label::factory()->create(['name' => 'CRM']);
        $submodule = Label::factory()->childOf($module->id)->create(['name' => 'Clientes']);

        $project->modules()->syncWithoutDetaching([$module->id]);

        $tree = app(TaskModuleCatalogService::class)->getProjectModuleTree();
        $projectModules = collect($tree[(string) $project->id] ?? []);
        $targetModule = $projectModules->firstWhere('id', (string) $module->id);

        expect($targetModule)->not->toBeNull()
            ->and($targetModule['childs'])->toContain([
                'id' => (string) $submodule->id,
                'name' => $submodule->name,
            ]);
    });

    it('usa módulos ativos globais como fallback quando o projeto não possui mapeamento', function () {
        $project = Project::factory()->create(['name' => 'Projeto sem vínculo']);
        $fallbackModule = Label::factory()->create(['name' => 'Helpdesk']);
        $fallbackChild = Label::factory()->childOf($fallbackModule->id)->create(['name' => 'Tickets']);

        $modules = app(TaskModuleCatalogService::class)->getModulesForProject($project->id);

        expect($modules->pluck('id')->all())->toContain($fallbackModule->id)
            ->and($modules->firstWhere('id', $fallbackModule->id)?->childs->pluck('id')->all())
            ->toContain($fallbackChild->id);
    });

    it('projectAllowsModule respeita os módulos vinculados ao projeto', function () {
        $project = Project::factory()->create();
        $allowedModule = Label::factory()->create(['name' => 'Financeiro']);
        $blockedModule = Label::factory()->create(['name' => 'CRM']);

        $project->modules()->syncWithoutDetaching([$allowedModule->id]);

        $service = app(TaskModuleCatalogService::class);

        expect($service->projectAllowsModule($project->id, $allowedModule->id))->toBeTrue()
            ->and($service->projectAllowsModule($project->id, $blockedModule->id))->toBeFalse();
    });

    it('submoduleBelongsToModule valida o vínculo pai e filho', function () {
        $module = Label::factory()->create(['name' => 'Financeiro']);
        $submodule = Label::factory()->childOf($module->id)->create(['name' => 'Faturamento']);
        $otherModule = Label::factory()->create(['name' => 'CRM']);

        $service = app(TaskModuleCatalogService::class);

        expect($service->submoduleBelongsToModule($module->id, $submodule->id))->toBeTrue()
            ->and($service->submoduleBelongsToModule($otherModule->id, $submodule->id))->toBeFalse();
    });

    it('sincroniza projetos e módulos a partir do catálogo de implantação do cliente', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $scheduleModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $rootLabel = Label::factory()->create(['name' => 'Financeiro RAT']);
        $child = Label::factory()->childOf($rootLabel->id)->create(['name' => 'Contas a Receber']);

        $company->scheduleModules()->sync([$scheduleModule->id]);

        $service = app(TaskModuleCatalogService::class);
        $catalog = $service->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $module = collect($catalog['projectModuleTree'][(string) $project->id] ?? [])->firstWhere('name', 'Financeiro RAT');

        expect($project)->not->toBeNull()
            ->and($module)->not->toBeNull()
            ->and($module['childs'])->toContain([
                'id' => (string) $child->id,
                'name' => 'Contas a Receber',
            ])
            ->and(Label::query()->where('schedule_module_id', $scheduleModule->id)->exists())->toBeTrue();
    });

    it('projectAllowsModule respeita o recorte do cliente no catálogo de implantação', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowed = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $blocked = Module::factory()->create(['name' => 'Estoque RAT', 'project' => 'ERP']);

        $company->scheduleModules()->sync([$allowed->id]);

        $service = app(TaskModuleCatalogService::class);
        $catalog = $service->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $allowedLabel = Label::query()->where('schedule_module_id', $allowed->id)->firstOrFail();

        $service->getCatalog();
        $blockedLabel = Label::query()->where('schedule_module_id', $blocked->id)->firstOrFail();

        expect($service->projectAllowsModule($project->id, $allowedLabel->id, $company->id))->toBeTrue()
            ->and($service->projectAllowsModule($project->id, $blockedLabel->id, $company->id))->toBeFalse();
    });

});
