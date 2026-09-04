<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskReportRepositoryInterface;
use App\Models\Tasks\Label;
use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Collection;

class TaskReportRepository implements TaskReportRepositoryInterface
{
    public function getActiveModules(): Collection
    {
        return Label::query()
            ->active()
            ->whereNull('parent_id')
            ->with(['childs' => fn ($query) => $query->active()->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    public function getActiveTasks(): Collection
    {
        return Task::query()
            ->active()
            ->with(['labels.parent', 'parent.customer'])
            ->get();
    }

    public function getParentTasksWithCustomer(?int $projectId): Collection
    {
        $query = Task::query()
            ->active()
            ->whereNull('parent_id')
            ->with(['project', 'customer', 'childs'])
            ->byPriority();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }

    public function getTasksWithProject(?int $projectId): Collection
    {
        $query = Task::query()
            ->active()
            ->with(['project', 'customer', 'labels', 'parent.customer'])
            ->byPriority();

        if ($projectId) {
            $query->where('project_id', $projectId);
        }

        return $query->get();
    }
}
