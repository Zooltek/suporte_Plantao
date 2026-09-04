<?php

namespace App\Repositories;

use App\Contracts\Repositories\ChangelogRepositoryInterface;
use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Models\Tasks\Project;
use Illuminate\Database\Eloquent\Collection;

class ChangelogRepository implements ChangelogRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getPendingChangelogs(?int $projectId): Collection
    {
        return Changelog::query()
            ->active()
            ->whereNull('version_id')
            ->with('user:id,name')
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findChangelog(int $id): Changelog
    {
        return Changelog::findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findProject(int $id): Project
    {
        return Project::findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function createChangelog(array $data): Changelog
    {
        return Changelog::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateChangelog(Changelog $changelog, array $data): Changelog
    {
        $changelog->update($data);

        return $changelog->fresh();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteChangelog(Changelog $changelog): bool
    {
        return $changelog->update(['status' => 0]);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionsByProject(int $projectId): Collection
    {
        return Version::where('project_id', $projectId)
            ->with(['changelogs' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getMaxSortOrder(int $projectId): int
    {
        return (int) (Changelog::where('project_id', $projectId)->max('sort_order') ?? 0);
    }
}
