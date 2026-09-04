<?php

use App\Models\Company;
use App\Models\Schedule\Module;
use App\Models\Schedule\Record;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Services\Admin\Tasks\TaskModuleCatalogService;

/**
 * Cobre o Bug 4: SQLSTATE[42S02] Table 'companies' doesn't exist.
 *
 * Raiz: TaskStoreRequest usava 'exists:companies,id' para validar customer_id,
 * mas a tabela real é 'customers'. O Laravel lançava QueryException no lugar
 * de retornar um erro de validação, quebrando o fluxo de criação de tarefas.
 */
describe('TaskStoreRequest — validação customer_id contra tabela customers (Bug 4)', function () {

    it('customer_id inexistente retorna erro de validação, não exceção SQL', function () {
        // Antes do fix: QueryException "Table 'companies' doesn't exist"
        // Após o fix:   erro de validação 'customer_id' retornado corretamente
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Conteúdo',
            'user_id' => $admin->id,
            'customer_id' => 99999,
        ])->assertSessionHasErrors('customer_id');
    });

    it('customer_id existente na tabela customers é aceito sem erro', function () {
        $admin = actingAsAdmin();
        $customer = Company::factory()->create(); // Company mapeia para 'customers'

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com Cliente',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'customer_id' => $customer->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', ['customer_id' => $customer->id]);
    });

    it('customer_id nulo é aceito — campo é opcional', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa sem cliente',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'customer_id' => null,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');
    });

    it('customer_id omitido é aceito — campo é opcional', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa sem campo cliente',
            'content' => 'Descrição',
            'user_id' => $admin->id,
        ])->assertRedirect(route('tasks.index'));
    });

    it('validação falha por user_id inválido, não por customer_id ausente', function () {
        // Regressão: garante que a validação do customer_id não "engole" outros erros
        actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Descrição',
            'user_id' => 99999,
        ])->assertSessionHasErrors('user_id')
            ->assertSessionDoesntHaveErrors('customer_id');
    });

    it('project_id inexistente retorna erro de validação', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com projeto inválido',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'project_id' => 99999,
        ])->assertSessionHasErrors('project_id');
    });

    it('project_id inativo retorna erro de validação', function () {
        $admin = actingAsAdmin();
        $project = Project::factory()->create([
            'user_id' => $admin->id,
            'status' => 0,
        ]);

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com projeto inativo',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'project_id' => $project->id,
        ])->assertSessionHasErrors('project_id');
    });

    it('rejeita módulo que não pertence ao projeto informado', function () {
        $admin = actingAsAdmin();
        $project = Project::factory()->create(['user_id' => $admin->id]);
        $allowedModule = Label::factory()->create(['name' => 'Financeiro']);
        $disallowedModule = Label::factory()->create(['name' => 'CRM']);

        $project->modules()->syncWithoutDetaching([$allowedModule->id]);

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com módulo inválido para o projeto',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'module_label_id' => $disallowedModule->id,
        ])->assertSessionHasErrors('module_label_id');
    });

    it('rejeita submódulo que não pertence ao módulo informado', function () {
        $admin = actingAsAdmin();
        $project = Project::factory()->create(['user_id' => $admin->id]);
        $module = Label::factory()->create(['name' => 'Estoque']);
        $otherModule = Label::factory()->create(['name' => 'Financeiro']);
        $submodule = Label::factory()->childOf($otherModule->id)->create(['name' => 'Cobrança']);

        $project->modules()->syncWithoutDetaching([$module->id]);

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com submódulo inválido',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'module_label_id' => $module->id,
            'submodule_label_id' => $submodule->id,
        ])->assertSessionHasErrors('submodule_label_id');
    });

    it('rejeita projeto fora do catálogo de implantação do cliente', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowedModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $blockedModule = Module::factory()->create(['name' => 'Fiscal RAT', 'project' => 'Fiscal']);

        $company->scheduleModules()->sync([$allowedModule->id]);

        $catalogService = app(TaskModuleCatalogService::class);
        $catalogService->getCatalog();

        $blockedProject = Project::query()
            ->where('schedule_project_name', 'Fiscal')
            ->firstOrFail();

        $this->post(route('tasks.store'), [
            'title' => 'Projeto inválido para o cliente',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'customer_id' => $company->id,
            'project_id' => $blockedProject->id,
        ])->assertSessionHasErrors('project_id');
    });

    it('rejeita módulo fora do catálogo de implantação do cliente', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowedModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $blockedModule = Module::factory()->create(['name' => 'Estoque RAT', 'project' => 'ERP']);

        $company->scheduleModules()->sync([$allowedModule->id]);

        $catalogService = app(TaskModuleCatalogService::class);
        $customerCatalog = $catalogService->getCatalog($company->id);
        $catalogService->getCatalog();

        $project = $customerCatalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $blockedLabel = Label::query()->where('schedule_module_id', $blockedModule->id)->firstOrFail();

        $this->post(route('tasks.store'), [
            'title' => 'Módulo inválido para o cliente',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'customer_id' => $company->id,
            'project_id' => $project->id,
            'module_label_id' => $blockedLabel->id,
        ])->assertSessionHasErrors('module_label_id');
    });

    it('aceita projeto e módulo do catálogo global sem cliente informado', function () {
        actingAsAdmin();
        $globalModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);

        $catalog = app(TaskModuleCatalogService::class)->getCatalog();
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $label = Label::query()->where('schedule_module_id', $globalModule->id)->firstOrFail();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa global sem cliente',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'project_id' => $project->id,
            'module_label_id' => $label->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa global sem cliente',
            'customer_id' => null,
            'project_id' => $project->id,
        ]);
    });

    it('rejeita RAT vinculado a outro cliente', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $module = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);

        $company->scheduleModules()->sync([$module->id]);

        $catalog = app(TaskModuleCatalogService::class)->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $label = Label::query()->where('schedule_module_id', $module->id)->firstOrFail();
        $foreignRecord = Record::factory()->create([
            'customer_id' => $otherCompany->id,
            'module_id' => $module->id,
        ]);

        $this->post(route('tasks.store'), [
            'title' => 'RAT de outro cliente',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'customer_id' => $company->id,
            'project_id' => $project->id,
            'module_label_id' => $label->id,
            'rat_record_ids' => [$foreignRecord->id],
        ])->assertSessionHasErrors('rat_record_ids');
    });

    it('rejeita RAT fora do projeto selecionado', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowedModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $foreignProjectModule = Module::factory()->create(['name' => 'Fiscal RAT', 'project' => 'Fiscal']);

        $company->scheduleModules()->sync([$allowedModule->id]);

        $catalog = app(TaskModuleCatalogService::class)->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $label = Label::query()->where('schedule_module_id', $allowedModule->id)->firstOrFail();
        $foreignProjectRecord = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $foreignProjectModule->id,
        ]);

        $this->post(route('tasks.store'), [
            'title' => 'RAT de outro projeto',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'customer_id' => $company->id,
            'project_id' => $project->id,
            'module_label_id' => $label->id,
            'rat_record_ids' => [$foreignProjectRecord->id],
        ])->assertSessionHasErrors('rat_record_ids');
    });

    it('rejeita RAT fora do módulo técnico selecionado', function () {
        actingAsAdmin();
        $company = Company::factory()->create();
        $allowedModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $foreignModule = Module::factory()->create(['name' => 'Estoque RAT', 'project' => 'ERP']);

        $company->scheduleModules()->sync([$allowedModule->id]);

        $catalog = app(TaskModuleCatalogService::class)->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $label = Label::query()->where('schedule_module_id', $allowedModule->id)->firstOrFail();
        $foreignModuleRecord = Record::factory()->create([
            'customer_id' => $company->id,
            'module_id' => $foreignModule->id,
        ]);

        $this->post(route('tasks.store'), [
            'title' => 'RAT de outro módulo técnico',
            'content' => 'Descrição',
            'user_id' => auth('admin')->id(),
            'customer_id' => $company->id,
            'project_id' => $project->id,
            'module_label_id' => $label->id,
            'rat_record_ids' => [$foreignModuleRecord->id],
        ])->assertSessionHasErrors('rat_record_ids');
    });

});
