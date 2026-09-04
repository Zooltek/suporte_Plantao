<?php

namespace App\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;

interface TaskReportRepositoryInterface
{
    /**
     * Retorna módulos raiz ativos usados no relatório operacional por módulo.
     */
    public function getActiveModules(): Collection;

    /**
     * Retorna tasks ativas com eager loading de labels, parent e customer.
     */
    public function getActiveTasks(): Collection;

    /**
     * Retorna tasks pai ativas (sem parent) com eager loading de project,
     * customer e childs, ordenadas por prioridade. Tarefas gerais (sem cliente
     * ou projeto) também são incluídas — o agrupamento usa "Sem Cliente"/"Sem Projeto".
     * Filtra por projeto quando $projectId for fornecido.
     */
    public function getParentTasksWithCustomer(?int $projectId): Collection;

    /**
     * Retorna tasks ativas com eager loading de project, customer, labels e
     * parent.customer, ordenadas por prioridade. Tarefas gerais (sem projeto)
     * também são incluídas — o agrupamento usa "Sem Projeto".
     * Filtra por projeto quando $projectId for fornecido.
     */
    public function getTasksWithProject(?int $projectId): Collection;
}
