<?php

use App\Models\Company;
use App\Models\Tasks\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// ── Fluxo 5 — Minhas Tarefas na inbox web compartilhada ──────────────────────

describe('Fluxo 5 — Minhas Tarefas na inbox web compartilhada', function () {

    it('staff acessa a inbox compartilhada pela rota canônica /tasks', function () {
        actingAsAgent();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index')
            ->assertSee('Minhas Tarefas')
            ->assertSee('Nova Tarefa')
            ->assertSee('Notificações');
    });

    it('usuário cria tarefa com cliente pela inbox web sem erro de referencia de tabela', function () {
        $admin = actingAsAdmin();
        $customer = Company::factory()->create();

        $response = $this->post(route('tasks.store'), [
            'title' => 'Tarefa Homologação',
            'content' => 'Conteúdo da tarefa de homologação',
            'user_id' => $admin->id,
            'customer_id' => $customer->id,
        ]);

        $response->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa Homologação',
            'customer_id' => $customer->id,
            'author_id' => $admin->id,
            'status' => 'pen',
        ]);
    });

    it('customer_id inválido retorna erro de validação na inbox web', function () {
        $admin = actingAsAdmin();

        $response = $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Conteúdo',
            'user_id' => $admin->id,
            'customer_id' => 99999,
        ]);

        $response->assertSessionHasErrors('customer_id');
    });

    it('criação de tarefa sem cliente continua aceita porque o campo é opcional', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa Sem Cliente',
            'content' => 'Conteúdo',
            'user_id' => $admin->id,
        ])->assertRedirect(route('tasks.index'));

        $this->assertDatabaseHas('tasks', ['title' => 'Tarefa Sem Cliente']);
    });

    it('tarefa criada com imagem persiste o anexo no banco e em disco', function () {
        Storage::fake('local');
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com Imagem',
            'content' => 'Evidência de tela',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->image('evidencia.png', 1280, 720),
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $task = Task::where('title', 'Tarefa com Imagem')->firstOrFail();
        $this->assertDatabaseHas('task_attachments', [
            'task_id' => $task->id,
            'file_name' => 'evidencia.png',
        ]);

        $attachment = $task->attachments()->first();
        Storage::disk('local')->assertExists($attachment->file_path);
    });

    it('tarefa criada com PDF persiste o attachment corretamente', function () {
        Storage::fake('local');
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa com PDF',
            'content' => 'Documento de especificação',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->create('spec.pdf', 1024, 'application/pdf'),
        ])->assertRedirect(route('tasks.index'));

        $task = Task::where('title', 'Tarefa com PDF')->firstOrFail();
        $this->assertDatabaseHas('task_attachments', ['task_id' => $task->id, 'file_name' => 'spec.pdf']);
    });

    it('arquivo acima de 50 MB falha na validação e não cria a tarefa', function () {
        $admin = actingAsAdmin();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa Arquivo Gigante',
            'content' => 'Conteúdo',
            'user_id' => $admin->id,
            'file' => UploadedFile::fake()->create('enorme.zip', 52_000, 'application/zip'),
        ])->assertSessionHasErrors('file');

        $this->assertDatabaseMissing('tasks', ['title' => 'Tarefa Arquivo Gigante']);
    });

    it('visitante não autenticado é redirecionado ao tentar criar tarefa pela inbox', function () {
        $this->post(route('tasks.store'), [
            'title' => 'Tarefa',
            'content' => 'Conteúdo',
            'user_id' => 1,
        ])->assertRedirect();
    });

});

// ── Fluxo 6 — Tramitação visível ao usuário ──────────────────────────────────

describe('Fluxo 6 — Tramitação visível ao usuário', function () {

    it('usuário visualiza detalhes e edita a tarefa pela inbox web compartilhada', function () {
        $admin = actingAsAdmin();
        $task = Task::factory()->create([
            'user_id' => $admin->id,
            'author_id' => $admin->id,
            'status' => 'pen',
            'title' => 'Tarefa pendente visível na inbox',
            'content' => 'Descrição inicial da tarefa visível ao usuário.',
        ]);

        $response = $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index')
            ->assertSee('Minhas Tarefas')
            ->assertSee('Notificações')
            ->assertSee('Visualizar')
            ->assertSee('Editar');

        expect($response->viewData('tasks')->pluck('title')->all())
            ->toContain('Tarefa pendente visível na inbox');

        $this->from(route('tasks.index'))
            ->patch(route('tasks.update', $task), [
                'title' => 'Tarefa pendente atualizada pela inbox',
                'content' => 'Descrição editada com status revisado.',
                'user_id' => $admin->id,
                'status' => 'pro',
                'editing_task_id' => $task->id,
            ])
            ->assertRedirect(route('tasks.index', ['task' => $task->id]))
            ->assertSessionHas('success', 'Tarefa atualizada com sucesso.');

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Tarefa pendente atualizada pela inbox',
            'status' => 'pro',
        ]);
    });

});
