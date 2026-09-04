<?php

use App\Models\Customer;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use App\Models\Tasks\Task;
use App\Models\User;
use App\Services\API\V1\Tasks\ReportService;

describe('ReportService', function () {

    beforeEach(function () {
        $this->service = app(ReportService::class);
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->customer = Customer::factory()->create();
    });

    it('getClientReportData retorna tarefas com projeto e cliente', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'parent_id' => null,
            'status' => 'new',
        ]);

        $data = $this->service->getClientReportData();

        expect($data)->not->toBeEmpty();
        expect($data[0])->toHaveKeys(['project_name', 'customers']);
        expect($data[0]['customers'])->not->toBeEmpty();
    });

    it('getClientReportData inclui tarefas gerais sem projeto no grupo "Sem Projeto"', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => null,
            'customer_id' => $this->customer->id,
            'parent_id' => null,
            'status' => 'new',
        ]);

        $data = $this->service->getClientReportData();

        expect(collect($data)->pluck('project_name'))->toContain('Sem Projeto');
    });

    it('getClientReportData inclui tarefas gerais sem cliente e sem projeto', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => null,
            'customer_id' => null,
            'parent_id' => null,
            'status' => 'new',
        ]);

        $data = $this->service->getClientReportData();

        $semProjeto = collect($data)->firstWhere('project_name', 'Sem Projeto');

        expect($semProjeto)->not->toBeNull()
            ->and(collect($semProjeto['customers'])->pluck('customer_name'))->toContain('Sem Cliente');
    });

    it('getClientReportData ignora tarefas com status inativo (can/bin/rej)', function () {
        foreach (['can', 'bin', 'rej'] as $status) {
            Task::factory()->create([
                'user_id' => $this->user->id,
                'author_id' => $this->user->id,
                'project_id' => $this->project->id,
                'customer_id' => $this->customer->id,
                'parent_id' => null,
                'status' => $status,
            ]);
        }

        $data = $this->service->getClientReportData();

        expect($data)->toBeEmpty();
    });

    it('getModuleReportData agrupa tarefas por projeto e label', function () {
        $label = Label::factory()->create(['user_id' => $this->user->id, 'is_active' => true]);

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'pen',
        ]);
        $task->labels()->attach($label->id);

        $data = $this->service->getModuleReportData();

        expect($data)->not->toBeEmpty();
        expect($data[0])->toHaveKeys(['project_name', 'labels']);
    });

    it('getModuleReportData filtra por projectId quando informado', function () {
        $other = Project::factory()->create(['user_id' => $this->user->id]);

        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
            'status' => 'new',
        ]);
        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $other->id,
            'status' => 'new',
        ]);

        $data = $this->service->getModuleReportData($this->project->id);

        $projectNames = collect($data)->pluck('project_name');
        expect($projectNames)->toContain($this->project->name)
            ->not->toContain($other->name);
    });

    it('getCarlosReportData agrupa tarefas pelo módulo raiz do catálogo novo', function () {
        $module = Label::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'parent_id' => null,
        ]);

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'status' => 'pen',
        ]);
        $task->labels()->attach($module->id);

        $data = $this->service->getCarlosReportData();

        expect($data['data'])->toHaveKey($module->id)
            ->and($data['data'][$module->id]['tasks'])->toHaveCount(1)
            ->and($data['data'][$module->id]['tasks'][0]->id)->toBe($task->id);
    });

    it('getCarlosReportData consolida subtarefa legada no módulo pai', function () {
        $module = Label::factory()->create([
            'user_id' => $this->user->id,
            'is_active' => true,
            'parent_id' => null,
        ]);
        $submodule = Label::factory()->childOf($module->id)->create([
            'user_id' => $this->user->id,
            'is_active' => true,
        ]);

        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => $this->project->id,
            'customer_id' => $this->customer->id,
            'status' => 'pen',
        ]);
        $task->labels()->attach($submodule->id);

        $data = $this->service->getCarlosReportData();

        expect($data['data'])->toHaveKey($module->id)
            ->and($data['data'][$module->id]['tasks'])->toHaveCount(1)
            ->and($data['data'][$module->id]['tasks'][0]->id)->toBe($task->id);
    });

    it('getCarlosReportData inclui tarefas gerais sem módulo no grupo "Geral"', function () {
        $task = Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => null,
            'customer_id' => null,
            'status' => 'pen',
        ]);

        $data = $this->service->getCarlosReportData();

        expect($data['data'])->toHaveKey('general')
            ->and($data['data']['general']['module']->name)->toBe('Geral (sem módulo)')
            ->and(collect($data['data']['general']['tasks'])->pluck('id'))->toContain($task->id);
    });

    it('getModuleReportData inclui tarefas gerais sem projeto em "Sem Projeto"', function () {
        Task::factory()->create([
            'user_id' => $this->user->id,
            'author_id' => $this->user->id,
            'project_id' => null,
            'status' => 'pen',
        ]);

        $data = $this->service->getModuleReportData();

        expect(collect($data)->pluck('project_name'))->toContain('Sem Projeto');
    });

});
