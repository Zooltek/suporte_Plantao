<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository implements ProjectRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getActiveProjects(): Collection
    {
        return Project::query()
            ->active()
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getUserProjectIds(int $userId): array
    {
        return UserProject::where('user_id', $userId)
            ->pluck('project_id')
            ->toArray();
    }

    /**
     * {@inheritDoc}
     */
    public function findParticipation(int $projectId, int $userId): ?UserProject
    {
        return UserProject::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function deleteParticipation(UserProject $participation): void
    {
        $participation->delete();
    }

    /**
     * {@inheritDoc}
     */
    public function createParticipation(int $projectId, int $userId): UserProject
    {
        return UserProject::create([
            'user_id'    => $userId,
            'project_id' => $projectId,
        ]);
    }
}
