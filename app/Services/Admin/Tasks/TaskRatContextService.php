<?php

namespace App\Services\Admin\Tasks;

use App\Models\Schedule\Record;
use App\Models\Tasks\Label;
use App\Models\Tasks\Project;
use Illuminate\Database\Eloquent\Collection;

class TaskRatContextService
{
    private const RECENT_RECORD_LIMIT = 12;

    /**
     * @return Collection<int, Record>
     */
    public function getRecentRecords(?int $customerId, int $limit = self::RECENT_RECORD_LIMIT): Collection
    {
        if (! $customerId) {
            return new Collection;
        }

        return Record::query()
            ->with([
                'module:id,name,project',
                'agent:id,name',
                'schedule:id,title,obs,status,start_at,customer_id',
            ])
            ->where('customer_id', $customerId)
            ->orderByDesc('start')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getSerializedRecentRecords(?int $customerId, int $limit = self::RECENT_RECORD_LIMIT): array
    {
        return $this->getRecentRecords($customerId, $limit)
            ->map(function (Record $record): array {
                $schedule = $record->schedule;
                $scheduleId = $schedule?->id ?? $record->schedule_id;

                return [
                    'id' => (string) $record->id,
                    'scheduleId' => $scheduleId ? (string) $scheduleId : '',
                    'scheduleTitle' => $schedule?->display_title
                        ?? $schedule?->title
                        ?? $record->obs
                        ?? "RAT #{$record->id}",
                    'scheduleStatus' => (string) ($schedule?->status ?? ''),
                    'scheduleUrl' => $scheduleId
                        ? route('agent.schedules.show', ['schedule' => $scheduleId])
                        : null,
                    'printUrl' => $scheduleId
                        ? route('agent.record.print', ['schedule' => $scheduleId, 'record' => $record->id])
                        : null,
                    'moduleId' => (string) ($record->module_id ?? ''),
                    'moduleName' => (string) ($record->module?->name ?? 'Módulo não informado'),
                    'projectName' => $this->normalizeProjectName($record->module?->project),
                    'agentName' => (string) ($record->agent?->name ?? ''),
                    'startedAt' => $record->start?->toIso8601String(),
                    'endedAt' => $record->end?->toIso8601String(),
                    'version' => trim((string) ($record->version ?? '')),
                    'release' => trim((string) ($record->release ?? '')),
                    'status' => (int) ($record->status ?? 0),
                    'statusLabel' => (int) ($record->status ?? 0) === 1 ? 'Ativo' : 'Arquivado',
                    'resolved' => (bool) ($record->resolved ?? false),
                    'notes' => trim((string) ($record->obs ?? '')),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int|string>  $recordIds
     */
    public function recordsBelongToCustomer(array $recordIds, int $customerId): bool
    {
        $recordIds = $this->normalizeRecordIds($recordIds);

        if ($recordIds === []) {
            return true;
        }

        return Record::query()
            ->whereIn('id', $recordIds)
            ->where('customer_id', $customerId)
            ->count() === count($recordIds);
    }

    /**
     * @param  array<int, int|string>  $recordIds
     */
    public function recordsBelongToProject(array $recordIds, string $projectName): bool
    {
        $recordIds = $this->normalizeRecordIds($recordIds);

        if ($recordIds === []) {
            return true;
        }

        $normalizedProject = $this->normalizeProjectName($projectName);

        return Record::query()
            ->whereIn('id', $recordIds)
            ->whereHas('module', function ($query) use ($normalizedProject): void {
                $query->whereRaw('LOWER(TRIM(COALESCE(project, ?))) = ?', [
                    'Geral',
                    mb_strtolower($normalizedProject),
                ]);
            })
            ->count() === count($recordIds);
    }

    /**
     * @param  array<int, int|string>  $recordIds
     */
    public function recordsBelongToScheduleModule(array $recordIds, int $scheduleModuleId): bool
    {
        $recordIds = $this->normalizeRecordIds($recordIds);

        if ($recordIds === []) {
            return true;
        }

        return Record::query()
            ->whereIn('id', $recordIds)
            ->where('module_id', $scheduleModuleId)
            ->count() === count($recordIds);
    }

    public function resolveProjectName(?int $projectId): ?string
    {
        if (! $projectId) {
            return null;
        }

        $project = Project::query()->find($projectId);

        if (! $project) {
            return null;
        }

        return $this->normalizeProjectName($project->schedule_project_name ?: $project->name);
    }

    public function resolveScheduleModuleIdForLabel(?int $labelId): ?int
    {
        if (! $labelId) {
            return null;
        }

        $scheduleModuleId = Label::query()
            ->whereKey($labelId)
            ->value('schedule_module_id');

        return $scheduleModuleId ? (int) $scheduleModuleId : null;
    }

    /**
     * @param  array<int, int|string>  $recordIds
     * @return array<int, int>
     */
    private function normalizeRecordIds(array $recordIds): array
    {
        return collect($recordIds)
            ->map(fn ($id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeProjectName(?string $projectName): string
    {
        $projectName = trim((string) $projectName);

        return $projectName !== '' ? $projectName : 'Geral';
    }
}
