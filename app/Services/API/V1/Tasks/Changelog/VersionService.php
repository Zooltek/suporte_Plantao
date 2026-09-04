<?php

namespace App\Services\API\V1\Tasks\Changelog;

use App\Contracts\Repositories\VersionRepositoryInterface;
use App\Models\Tasks\Changelog\Version;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class VersionService
{
    public function __construct(
        private readonly VersionRepositoryInterface $versionRepository
    ) {}

    /**
     * Lista as versões de changelog, filtrando por projeto opcionalmente.
     */
    public function getVersions(?int $projectId): Collection
    {
        return $this->versionRepository->getVersionsByProject($projectId);
    }

    /**
     * Cria uma nova versão e vincula os changelogs pendentes a ela.
     */
    public function createVersion(array $data, int $userId): Version
    {
        $date      = $this->prepareReferenceDate($data);
        $projectId = (int) $data['project_id'];

        $this->validateVersionBusinessRules($projectId, $date);

        $changelogs = $this->resolvePendingChangelogs($projectId, $date);

        try {
            return DB::transaction(function () use ($data, $date, $changelogs, $userId, $projectId) {
                $version = $this->versionRepository->createVersion([
                    'project_id'     => $projectId,
                    'user_id'        => $userId,
                    'name'           => $data['name'] ?? null,
                    'reference_date' => $date,
                ]);

                return $this->versionRepository->assignChangelogsToVersion($version, $changelogs);
            });
        } catch (Exception $e) {
            Log::error("Erro ao criar versão: " . $e->getMessage());
            throw new Exception(json_encode(['status' => 'server_error']), 500);
        }
    }

    // --- Métodos privados de regra de negócio ---

    private function prepareReferenceDate(array $data): Carbon
    {
        $now     = now();
        $dateStr = $data['reference_date'] ?? $now->toDateString();
        $date    = Carbon::parse($dateStr);

        if (!empty($data['time'])) {
            [$hour, $minute] = explode(':', $data['time']);

            return $date->setTime((int) $hour, (int) $minute, (int) $now->second);
        }

        return $date->setTime($now->hour, $now->minute, $now->second);
    }

    private function validateVersionBusinessRules(int $projectId, Carbon $date): void
    {
        $now = now();

        if ($date->gt($now)) {
            throw new Exception(json_encode(['status' => 'future_date', 'time' => $date->format('H:i:s')]), 422);
        }

        $lastVersion = $this->versionRepository->getLatestVersion($projectId);

        if ($lastVersion && $date->lt($lastVersion->reference_date)) {
            throw new Exception(json_encode(['status' => 'false', 'message' => 'Data anterior à última versão']), 422);
        }
    }

    private function resolvePendingChangelogs(int $projectId, Carbon $date): \Illuminate\Database\Eloquent\Collection
    {
        $changelogs = $this->versionRepository->getPendingChangelogsUpTo($projectId, $date);

        if ($changelogs->isEmpty()) {
            throw new Exception(json_encode(['status' => 'no_changelog_count_available']), 422);
        }

        return $changelogs;
    }
}
