<?php

use App\Models\Department;
use App\Models\Schedule\Module as ScheduleModule;
use App\Models\Tasks\Label;
use App\Services\Admin\Tasks\TaskModuleCatalogService;

test('labels órfãos passam a aparecer no catálogo após backfill para schedule_record_module', function () {
    actingAsAdmin();

    $department = Department::factory()->create(['name' => 'Suporte Técnico']);

    $root = Label::factory()->create([
        'name'               => 'CRM',
        'is_active'          => true,
        'parent_id'          => null,
        'department_id'      => $department->id,
        'schedule_module_id' => null,
    ]);

    Label::factory()->create([
        'name'               => 'Follow-ups',
        'is_active'          => true,
        'parent_id'          => $root->id,
        'department_id'      => $department->id,
        'schedule_module_id' => null,
    ]);

    $module = ScheduleModule::query()->create([
        'name'    => $root->name,
        'project' => $department->name,
    ]);
    $root->forceFill(['schedule_module_id' => $module->id])->saveQuietly();

    $catalog = app(TaskModuleCatalogService::class)->getCatalog();

    $project = $catalog['projects']->firstWhere('name', 'Suporte Técnico');
    expect($project)->not->toBeNull();

    $rootLabels = $catalog['moduleCollections'][(string) $project->id];
    $crm = $rootLabels->firstWhere('name', 'CRM');

    expect($crm)->not->toBeNull()
        ->and($crm->childs->pluck('name'))->toContain('Follow-ups');
});

test('submoduleBelongsToModule reconhece filhos órfãos herdando via pai backfilled', function () {
    $root = Label::factory()->create([
        'name'               => 'CRM',
        'is_active'          => true,
        'parent_id'          => null,
        'schedule_module_id' => ScheduleModule::query()->create([
            'name'    => 'CRM',
            'project' => 'Suporte Técnico',
        ])->id,
    ]);

    $child = Label::factory()->create([
        'name'      => 'Follow-ups',
        'is_active' => true,
        'parent_id' => $root->id,
    ]);

    expect(app(TaskModuleCatalogService::class)->submoduleBelongsToModule($root->id, $child->id))->toBeTrue();
});
