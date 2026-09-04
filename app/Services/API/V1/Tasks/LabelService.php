<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\LabelRepositoryInterface;
use App\Models\Tasks\Label;
use App\Models\Tasks\TaskLabel;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class LabelService
{
    public function __construct(
        private readonly LabelRepositoryInterface $labelRepository,
    ) {}

    /**
     * Retorna a lista de etiquetas.
     * Se mode for 'module', filtra por departamento. Caso contrário, retorna etiquetas pessoais.
     */
    public function getLabels(?string $mode, ?int $requestedUserId, int $authUserId): Collection
    {
        if ($mode === 'module') {
            $departmentId = null;

            if ($requestedUserId) {
                /** @var User $user */
                $user = User::findOrFail($requestedUserId);
                $departmentId = $user->department_id ?? null;
            }

            return $this->labelRepository->getModuleLabels($departmentId);
        }

        return $this->labelRepository->getPersonalLabels($authUserId);
    }

    /**
     * Cria uma nova etiqueta pessoal.
     */
    public function createPersonalLabel(string $name, int $authUserId): Label
    {
        return $this->labelRepository->createLabel([
            'name'    => $name,
            'user_id' => $authUserId,
        ]);
    }

    /**
     * Vincula uma etiqueta a uma tarefa específica.
     */
    public function assignLabelToTask(int $taskId, int $labelId): TaskLabel
    {
        $task  = $this->labelRepository->findTaskOrFail($taskId);
        $label = $this->labelRepository->findLabelOrFail($labelId);

        if (!$task->canModify()) {
            throw new Exception('unauthorized');
        }

        if ($this->labelRepository->labelExistsOnTask($task->id, $label->id)) {
            throw new Exception('already_assigned');
        }

        $taskLabel = $this->labelRepository->attachLabelToTask($task->id, $label->id);
        $taskLabel->name = $label->name;

        return $taskLabel;
    }

    /**
     * Remove (inativa) uma etiqueta e seus vínculos.
     */
    public function destroyLabel(int $id): bool
    {
        $label = $this->labelRepository->findLabelOrFail($id);
        $this->labelRepository->deactivateLabel($label);

        return true;
    }
}
