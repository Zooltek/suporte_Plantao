<?php

use App\Models\Tasks\Attachment;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Models\Company;
use App\Models\Schedule\Module;
use App\Services\Admin\Tasks\TaskModuleCatalogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ─── Admin Tasks — store (web) ─────────────────────────────────────────────

describe('Admin Tasks — store (web)', function () {

    it('admin cria tarefa com campos obrigatórios', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa de Teste',
            'content' => 'Descrição da tarefa de teste',
            'user_id' => $admin->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa de Teste',
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
        ]);
    });

    it('criação sem título retorna erro de validação', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'content' => 'Sem título',
            'user_id' => $admin->id,
        ])->assertSessionHasErrors('title');
    });

    it('criação sem conteúdo retorna erro de validação', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa sem conteúdo',
            'user_id' => $admin->id,
        ])->assertSessionHasErrors('content');
    });

    it('user_id inválido retorna erro de validação', function () {
        actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Descrição',
            'user_id' => 99999,
        ])->assertSessionHasErrors('user_id');
    });

    it('tarefa criada aparece na listagem', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa Listada',
            'content' => 'Deve aparecer na lista',
            'user_id' => $admin->id,
        ]);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index');

        $this->assertDatabaseHas('tasks', ['title' => 'Tarefa Listada']);
    });

    it('delivery_at em formato Y-m-d é convertido e aceito', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com Prazo',
            'content' => 'Com prazo definido',
            'user_id' => $admin->id,
            'delivery_at' => '2026-12-31',
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa com Prazo',
            'delivery_at' => '2026-12-31 00:00:00',
        ]);
    });

    it('visitante não autenticado é redirecionado ao login', function () {
        $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Descrição',
            'user_id' => 1,
        ])->assertRedirect();
    });

    it('author_id é preenchido automaticamente como usuário logado', function () {
        $admin = actingAsAdmin();
        $outro = User::factory()->agent()->create();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa Delegada',
            'content' => 'Delegada a outro usuário',
            'user_id' => $outro->id,
        ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa Delegada',
            'user_id' => $outro->id,
            'author_id' => $admin->id,
        ]);
    });

    it('view de criação carrega a árvore de projeto com módulos e submódulos', function () {
        actingAsAdmin();

        $project = Project::factory()->create(['name' => 'Projeto Financeiro']);
        $module = Label::factory()->create(['name' => 'Financeiro']);
        $child = Label::factory()->childOf($module->id)->create(['name' => 'Contas a Receber']);
        $project->modules()->syncWithoutDetaching([$module->id]);

        $projectModuleTree = $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index')
            ->assertSee('module_label_id')
            ->assertSee('submodule_label_id')
            ->viewData('projectModuleTree');

        $projectModules = collect($projectModuleTree[(string) $project->id] ?? []);
        $target = $projectModules->firstWhere('id', (string) $module->id);

        expect($target)->not->toBeNull()
            ->and($target['childs'])->toContain([
                'id' => (string) $child->id,
                'name' => $child->name,
            ]);
    });

    it('admin cria tarefa com projeto e submódulo selecionados', function () {
        $admin = actingAsAdmin();
        $project = Project::factory()->create(['user_id' => $admin->id]);
        $module = Label::factory()->create(['name' => 'Estoque']);
        $submodule = Label::factory()->childOf($module->id)->create(['name' => 'Movimentação']);
        $project->modules()->syncWithoutDetaching([$module->id]);

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com módulo',
            'content' => 'Descrição com módulo e submódulo',
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'module_label_id' => $module->id,
            'submodule_label_id' => $submodule->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $task = Task::query()->where('title', 'Tarefa com módulo')->firstOrFail();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('label_task', [
            'task_id' => $task->id,
            'label_id' => $submodule->id,
        ]);

        $this->assertDatabaseMissing('label_task', [
            'task_id' => $task->id,
            'label_id' => $module->id,
        ]);
    });

    it('admin cria tarefa com catálogo canônico de implantação para o cliente selecionado', function () {
        $admin = actingAsAdmin();
        $company = Company::factory()->create();
        $ratModule = Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);
        $company->scheduleModules()->sync([$ratModule->id]);

        $catalog = app(TaskModuleCatalogService::class)->getCatalog($company->id);
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'ERP');
        $module = Label::query()->where('schedule_module_id', $ratModule->id)->firstOrFail();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com catálogo RAT',
            'content' => 'Descrição alinhada à implantação',
            'user_id' => $admin->id,
            'customer_id' => $company->id,
            'project_id' => $project->id,
            'module_label_id' => $module->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $task = Task::query()->where('title', 'Tarefa com catálogo RAT')->firstOrFail();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'customer_id' => $company->id,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('label_task', [
            'task_id' => $task->id,
            'label_id' => $module->id,
        ]);
    });

    it('view de criação não renderiza submódulo quando o catálogo atual só possui módulos raiz', function () {
        actingAsAdmin();
        Module::factory()->create(['name' => 'Financeiro RAT', 'project' => 'ERP']);

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index')
            ->assertDontSeeHtml('name="submodule_label_id"')
            ->assertDontSee('Este módulo não possui submódulos cadastrados.');
    });

    it('admin cria tarefa com catálogo técnico global sem informar cliente', function () {
        $admin = actingAsAdmin();
        $globalModule = Module::factory()->create(['name' => 'Contábil', 'project' => 'EasyMaster']);
        $catalog = app(TaskModuleCatalogService::class)->getCatalog();
        $project = $catalog['projects']->firstWhere('schedule_project_name', 'EasyMaster');
        $module = Label::query()->where('schedule_module_id', $globalModule->id)->firstOrFail();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa global com contexto técnico',
            'content' => 'Descrição alinhada ao catálogo técnico global',
            'user_id' => $admin->id,
            'project_id' => $project->id,
            'module_label_id' => $module->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $task = Task::query()->where('title', 'Tarefa global com contexto técnico')->firstOrFail();

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'customer_id' => null,
            'project_id' => $project->id,
        ]);

        $this->assertDatabaseHas('label_task', [
            'task_id' => $task->id,
            'label_id' => $module->id,
        ]);
    });

    it('view de criação remove o painel de RAT e deixa o cliente como opcional', function () {
        actingAsAdmin();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index')
            ->assertSee('Registrar tarefa com contexto técnico completo')
            ->assertSee('Cliente opcional')
            ->assertDontSee('RATs recentes do cliente');
    });

});

// ─── Bug 5 — Upload de arquivo na criação de tarefas ──────────────────────

describe('Admin Tasks — store com upload de arquivo (Bug 5)', function () {

    it('tarefa criada com imagem válida persiste attachment no banco', function () {
        Storage::fake('local');
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com Imagem',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->image('screenshot.png', 800, 600),
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $task = Task::where('title', 'Tarefa com Imagem')->firstOrFail();

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'file_name' => 'screenshot.png',
        ]);
    });

    it('tarefa criada com PDF válido persiste attachment', function () {
        Storage::fake('local');
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com PDF',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->create('documento.pdf', 512, 'application/pdf'),
        ])->assertRedirect(route('tasks.index'));

        $task = Task::where('title', 'Tarefa com PDF')->firstOrFail();

        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'file_name' => 'documento.pdf',
        ]);
    });

    it('arquivo é salvo em disco dentro de task-attachments', function () {
        Storage::fake('local');
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com Disco',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->image('evidencia.jpg'),
        ]);

        $attachment = Attachment::where('file_name', 'evidencia.jpg')->firstOrFail();

        Storage::disk('local')->assertExists($attachment->file_path);
    });

    it('tarefa sem arquivo é criada normalmente — campo file é opcional', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa sem Anexo',
            'content' => 'Descrição',
            'user_id' => $admin->id,
        ])->assertRedirect(route('tasks.index'));

        $task = Task::where('title', 'Tarefa sem Anexo')->firstOrFail();

        $this->assertDatabaseMissing('task_attachments', ['task_id' => $task->id]);
    });

    it('arquivo maior que 50 MB falha na validação', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa Arquivo Gigante',
            'content' => 'Descrição',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->create('enorme.zip', 52_000, 'application/zip'),
        ])->assertSessionHasErrors('file');
    });

});
