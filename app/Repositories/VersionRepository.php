<?php

namespace App\Repositories;

use App\Contracts\Repositories\VersionRepositoryInterface;
use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VersionRepository implements VersionRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVersionsByProject(?int $projectId): EloquentCollection
    {
        return Version::query()
            ->with(['changelogs' => fn($q) => $q->orderBy('sort_order', 'asc')])
            ->when($projectId, fn($q) => $q->where('project_id', $projectId))
            ->orderBy('reference_date', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function findVersion(int $id): Version
    {
        return Version::findOrFail($id);
    }

    /**
     * {@inheritdoc}
     */
    public function createVersion(array $data): Version
    {
        return Version::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function assignChangelogsToVersion(Version $version, Collection $changelogs): Version
    {
        Changelog::whereIn('id', $changelogs->pluck('id'))
            ->update(['version_id' => $version->id]);

        return $version->load('changelogs');
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestVersion(int $projectId): ?Version
    {
        return Version::where('project_id', $projectId)
            ->orderByDesc('reference_date')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getPendingChangelogsUpTo(int $projectId, Carbon $date): EloquentCollection
    {
        return Changelog::active()
            ->where('project_id', $projectId)
            ->whereNull('version_id')
            ->where('created_at', '<=', $date)
            ->get();
    }
}
