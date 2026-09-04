<?php

use App\Models\Company;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;

describe('Inbox de tarefas — filtros', function () {
    it('encontra a tarefa pelo ID informado na busca textual', function () {
        $agent = actingAsAgent();

        $targetTask = Task::factory()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'title' => 'Ajustar fluxo de baixa',
            'content' => 'Sem mencionar o número da tarefa no texto.',
        ]);

        Task::factory()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'title' => 'Outra demanda',
            'content' => 'Esta tarefa não deve aparecer na busca por ID.',
        ]);

        $response = $this->get(route('tasks.index', ['q' => '#' . $targetTask->id]))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index');

        expect($response->viewData('tasks')->pluck('id')->all())
            ->toEqual([$targetTask->id])
            ->and($response->viewData('search'))->toBe('#' . $targetTask->id);
    });

    it('aplica filtros por status, classificação, cliente e projeto', function () {
        $agent = actingAsAgent();
        $customer = Company::factory()->create(['trade_name' => 'Cliente Alvo']);
        $otherCustomer = Company::factory()->create(['trade_name' => 'Cliente Ruído']);
        $project = Project::factory()->create(['name' => 'ERP']);
        $otherProject = Project::factory()->create(['name' => 'WMS']);

        $matchingTask = Task::factory()->open()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'classification' => 'fix',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'title' => 'Correção elegível',
        ]);

        Task::factory()->done()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'classification' => 'fix',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'title' => 'Concluída fora do filtro',
        ]);

        Task::factory()->open()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'classification' => 'bug',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
            'title' => 'Classificação diferente',
        ]);

        Task::factory()->open()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'classification' => 'fix',
            'customer_id' => $otherCustomer->id,
            'project_id' => $project->id,
            'title' => 'Cliente diferente',
        ]);

        Task::factory()->open()->create([
            'user_id' => $agent->id,
            'author_id' => $agent->id,
            'classification' => 'fix',
            'customer_id' => $customer->id,
            'project_id' => $otherProject->id,
            'title' => 'Projeto diferente',
        ]);

        $response = $this->get(route('tasks.index', [
            'status' => 'open',
            'classification' => 'fix',
            'customer_id' => $customer->id,
            'project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertViewIs('tasks.mobile.index');

        expect($response->viewData('tasks')->pluck('id')->all())
            ->toEqual([$matchingTask->id])
            ->and($response->viewData('statusFilter'))->toBe('open')
            ->and($response->viewData('classificationFilter'))->toBe('fix')
            ->and($response->viewData('selectedCustomerFilter'))->toBe((string) $customer->id)
            ->and($response->viewData('selectedProjectFilter'))->toBe((string) $project->id);
    });
});
