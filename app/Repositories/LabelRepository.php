<?php

namespace App\Repositories;

use App\Contracts\Repositories\LabelRepositoryInterface;
use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use App\Models\Tasks\TaskLabel;
use Illuminate\Database\Eloquent\Collection;

class LabelRepository implements LabelRepositoryInterface
{
    /**
     * Retorna labels ativas raiz filtradas por módulo/departamento, com childs eager-loaded.
     */
    public function getModuleLabels(?int $departmentId): Collection
    {
        $query = Label::active()->whereNull('parent_id')->whereNull('user_id');

        if ($departmentId !== null) {
            $query->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereNull('department_id');
            });
        } else {
            $query->whereNull('department_id');
        }

        /** @var Collection<int, Label> $labels */
        $labels = $query->orderBy('name', 'asc')->with(['childs' => function ($q) {
            $q->orderBy('name', 'asc');
        }])->get();

        return $labels;
    }

    /**
     * Retorna labels ativas raiz do usuário.
     */
    public function getPersonalLabels(int $userId): Collection
    {
        return Label::active()
            ->whereNull('parent_id')
            ->where('user_id', $userId)
            ->get();
    }

    /**
     * Cria e persiste uma nova label.
     */
    public function createLabel(array $data): Label
    {
        return Label::create($data);
    }

    /**
     * Busca label pelo ID ou lança ModelNotFoundException.
     */
    public function findLabelOrFail(int $id): Label
    {
        return Label::findOrFail($id);
    }

    /**
     * Desativa a label e remove todos os TaskLabels vinculados.
     */
    public function deactivateLabel(Label $label): void
    {
        $label->is_active = false;
        $label->save();

        TaskLabel::where('label_id', $label->id)->delete();
    }

    /**
     * Busca Task pelo ID ou lança ModelNotFoundException.
     */
    public function findTaskOrFail(int $id): Task
    {
        return Task::findOrFail($id);
    }

    /**
     * Verifica se a label já está vinculada à task.
     */
    public function labelExistsOnTask(int $taskId, int $labelId): bool
    {
        return TaskLabel::where('task_id', $taskId)
                        ->where('label_id', $labelId)
                        ->exists();
    }

    /**
     * Cria o registro de vínculo TaskLabel e retorna a instância.
     */
    public function attachLabelToTask(int $taskId, int $labelId): TaskLabel
    {
        return TaskLabel::create([
            'task_id'  => $taskId,
            'label_id' => $labelId,
        ]);
    }
}
