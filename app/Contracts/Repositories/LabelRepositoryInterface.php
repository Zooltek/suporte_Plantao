<?php

namespace App\Contracts\Repositories;

use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskLabel;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface LabelRepositoryInterface
{
    /**
     * Retorna labels ativas raiz (sem parent_id) filtradas por departamento e/ou nulas,
     * com os filhos (childs) carregados via eager loading.
     *
     * @param  int|null $departmentId  Quando fornecido, inclui labels do depto + sem depto.
     *                                 Quando null, retorna apenas labels sem department_id.
     */
    public function getModuleLabels(?int $departmentId): Collection;

    /**
     * Retorna labels ativas raiz (sem parent_id) pertencentes ao usuário.
     */
    public function getPersonalLabels(int $userId): Collection;

    /**
     * Cria e persiste uma nova label, retornando a instância.
     */
    public function createLabel(array $data): Label;

    /**
     * Busca a label pelo ID. Lança ModelNotFoundException se não encontrar.
     */
    public function findLabelOrFail(int $id): Label;

    /**
     * Desativa a label (status = 0) e remove todos os TaskLabels vinculados.
     */
    public function deactivateLabel(Label $label): void;

    /**
     * Busca a Task pelo ID. Lança ModelNotFoundException se não encontrar.
     */
    public function findTaskOrFail(int $id): Task;

    /**
     * Verifica se uma label já está vinculada à task.
     */
    public function labelExistsOnTask(int $taskId, int $labelId): bool;

    /**
     * Cria o registro de vínculo label↔task (TaskLabel) e retorna a instância.
     */
    public function attachLabelToTask(int $taskId, int $labelId): TaskLabel;
}
