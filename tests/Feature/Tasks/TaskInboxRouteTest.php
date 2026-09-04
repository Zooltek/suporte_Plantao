<?php

describe('Inbox de tarefas — rotas canônicas', function () {
    it('admin acessa a inbox pela rota canônica', function () {
        actingAsAdmin();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index');
    });

    it('agente acessa a inbox pela rota canônica', function () {
        actingAsAgent();

        $this->get(route('tasks.index'))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index');
    });

    it('alias legado do admin redireciona para a rota canônica preservando filtros', function () {
        actingAsAdmin();

        $this->get('/admin/tasks/mobile?q=financeiro&status=done&task_id=42&classification=fix&customer_id=15&project_id=9')
            ->assertRedirect(route('tasks.index', [
                'q' => 'financeiro',
                'status' => 'done',
                'task_id' => 42,
                'classification' => 'fix',
                'customer_id' => 15,
                'project_id' => 9,
            ]));
    });

    it('alias legado do staff redireciona para a rota canônica preservando filtros', function () {
        actingAsAgent();

        $this->get('/tasks/mobile?q=financeiro&status=done&task_id=42&classification=fix&customer_id=15&project_id=9')
            ->assertRedirect(route('tasks.index', [
                'q' => 'financeiro',
                'status' => 'done',
                'task_id' => 42,
                'classification' => 'fix',
                'customer_id' => 15,
                'project_id' => 9,
            ]));
    });

    it('agente cria tarefa pela rota canônica compartilhada', function () {
        $agent = actingAsAgent();

        $this->post(route('tasks.store'), [
            'title' => 'Tarefa criada pelo agente',
            'content' => 'Fluxo compartilhado da inbox',
            'user_id' => $agent->id,
        ])->assertRedirect(route('tasks.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('tasks', [
            'title' => 'Tarefa criada pelo agente',
            'user_id' => $agent->id,
            'author_id' => $agent->id,
        ]);
    });
});
