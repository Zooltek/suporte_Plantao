<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserProjectRepositoryInterface;
use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use Illuminate\Database\Eloquent\Collection;

class UserProjectRepository implements UserProjectRepositoryInterface
{
    public function allForUser(int $userId): Collection
    {
        return UserProject::query()
            ->where('tasks_users_projects.user_id', $userId)
            ->join('tasks_projects', 'tasks_users_projects.project_id', '=', 'tasks_projects.id')
            ->select('tasks_users_projects.*')
            ->with('project')
            ->orderBy('tasks_projects.name', 'asc')
            ->get();
    }

    public function existsForUser(int $userId, int $projectId): bool
    {
        return UserProject::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->exists();
    }

    public function findProject(int $projectId): Project
    {
        return Project::findOrFail($projectId);
    }

    public function create(array $data): UserProject
    {
        return UserProject::create($data)->load('project');
    }

    public function findByIdAndUser(int $id, int $userId): UserProject
    {
        return UserProject::where('id', $id)
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function findByProjectAndUser(int $projectId, int $userId): ?UserProject
    {
        return UserProject::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->first();
    }

    public function delete(UserProject $userProject): bool
    {
        return (bool) $userProject->delete();
    }
}
