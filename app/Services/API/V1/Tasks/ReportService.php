<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\TaskReportRepositoryInterface;
use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly TaskReportRepositoryInterface $repository,
    ) {}

    /**
     * Prepara e estrutura os dados para o relatório Carlos (Tarefas agrupadas por Módulo).
     */
    public function getCarlosReportData(): array
    {
        $modules = $this->repository->getActiveModules();
        $tasks = $this->repository->getActiveTasks();

        $reportData = $modules->keyBy('id')->map(fn ($module) => [
            'module' => $module,
            'tasks' => [],
        ])->toArray();

        $generalTasks = [];

        foreach ($tasks as $task) {
            if ($task->parent_id && $task->parent?->customer) {
                $task->customer = $task->parent->customer;
            }

            $taskModules = $this->resolveRootModulesForTask($task);

            // Tarefas gerais (sem módulo/etiqueta) não podem ficar órfãs do relatório
            if ($taskModules->isEmpty()) {
                $generalTasks[] = $task;

                continue;
            }

            foreach ($taskModules as $module) {
                if (isset($reportData[$module->id])) {
                    $reportData[$module->id]['tasks'][] = $task;
                }
            }
        }

        if ($generalTasks !== []) {
            $reportData['general'] = [
                'module' => (object) ['id' => 'general', 'name' => 'Geral (sem módulo)'],
                'tasks' => $generalTasks,
            ];
        }

        return [
            'tasks' => $tasks,
            'data' => $reportData,
        ];
    }

    /**
     * @return Collection<int, Label>
     */
    private function resolveRootModulesForTask(Task $task): Collection
    {
        return $task->labels
            ->map(function (Label $label): ?Label {
                if (! $label->is_active) {
                    return null;
                }

                if ($label->parent_id) {
                    $parent = $label->parent;

                    return $parent?->is_active ? $parent : null;
                }

                return $label;
            })
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Prepara os dados para o relatório "Por Cliente".
     *
     * Estrutura retornada (compatível com a view tasks.reports.por-cliente):
     * [
     *   [
     *     'project_name' => string,
     *     'customers'    => [
     *       ['customer_name' => string, 'tasks' => Task[] (com ->childs carregados)]
     *     ]
     *   ]
     * ]
     *
     * Todas as tasks pai (parent_id = null) são incluídas — tarefas gerais sem
     * cliente ou projeto entram nos grupos "Sem Cliente"/"Sem Projeto".
     * As subtarefas são carregadas via eager-loading em childs.
     */
    public function getClientReportData(?int $projectId = null): array
    {
        $tasks = $this->repository->getParentTasksWithCustomer($projectId);

        // Agrupa: projeto → cliente → tasks
        $grouped = [];

        foreach ($tasks as $task) {
            $projectName = $task->project?->name ?? 'Sem Projeto';
            $customerName = $task->customer?->company ?? $task->customer?->name ?? 'Sem Cliente';

            $grouped[$projectName][$customerName][] = $task;
        }

        // Converte para o formato de array indexado que a view espera
        $data = [];

        foreach ($grouped as $projectName => $customers) {
            $customerRows = [];

            foreach ($customers as $customerName => $customerTasks) {
                $customerRows[] = [
                    'customer_name' => $customerName,
                    'tasks' => $customerTasks,
                ];
            }

            $data[] = [
                'project_name' => $projectName,
                'customers' => $customerRows,
            ];
        }

        return $data;
    }

    /**
     * Prepara os dados para o relatório "Por Módulo/Etiqueta".
     *
     * Estrutura retornada (compatível com a view tasks.reports.por-modulo):
     * [
     *   [
     *     'project_name' => string,
     *     'labels'       => [
     *       ['label_name' => string, 'tasks' => Task[]]
     *     ]
     *   ]
     * ]
     *
     * Agrupa tasks ativas por Projeto → Label. Tarefas gerais sem projeto
     * entram em "Sem Projeto" e tasks sem nenhuma label em "Sem Etiqueta".
     */
    public function getModuleReportData(?int $projectId = null): array
    {
        $tasks = $this->repository->getTasksWithProject($projectId);

        // Agrupa: projeto → label → tasks
        $grouped = [];

        foreach ($tasks as $task) {
            if ($task->parent_id && ! $task->customer && $task->parent?->customer) {
                $task->customer = $task->parent->customer;
            }

            $projectName = $task->project?->name ?? 'Sem Projeto';

            $labels = $task->labels->isNotEmpty()
                ? $task->labels
                : collect([(object) ['name' => 'Sem Etiqueta']]);

            foreach ($labels as $label) {
                $labelName = $label->name ?? 'Sem Etiqueta';
                $grouped[$projectName][$labelName][] = $task;
            }
        }

        // Converte para o formato indexado que a view espera
        $data = [];

        foreach ($grouped as $projectName => $labels) {
            $labelRows = [];

            foreach ($labels as $labelName => $labelTasks) {
                $labelRows[] = [
                    'label_name' => $labelName,
                    'tasks' => $labelTasks,
                ];
            }

            $data[] = [
                'project_name' => $projectName,
                'labels' => $labelRows,
            ];
        }

        return $data;
    }
}
