<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Tasks\Notification;
use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TaskRepository implements TaskRepositoryInterface
{
    private const OPEN_STATUSES = ['new', 'pen', 'pro', 'sto'];

    private array $taskRelations = [
        'labels', 'author', 'tester', 'user',
        'software', 'customer.software', 'attachments', 'project',
        'references.reference', 'referenced.referenced',
        'ratRecords.module', 'ratRecords.agent', 'ratRecords.schedule',
    ];

    /**
     * Busca tarefa pelo ID com todas as relações carregadas.
     */
    public function findWithRelations(int $id): ?Task
    {
        return Task::with($this->taskRelations)->find($id);
    }

    /**
     * Busca tarefa pelo ID sem relações. Lança ModelNotFoundException.
     */
    public function findOrFail(int $id): Task
    {
        return Task::findOrFail($id);
    }

    /**
     * Retorna coleção de tasks com filtros aplicados e eager loading.
     */
    public function getFiltered(array $filters): Collection
    {
        $query = !empty($filters['query'])
            ? Task::search($filters['query'])
            : Task::query();

        $this->applyFilters($query, $filters);

        $limit = $filters['limite'] ?? null;

        return $query->with($this->taskRelations)
            ->when($limit, fn($q) => $q->limit((int) $limit))
            ->get();
    }

    /**
     * Retorna tasks do usuário (responsável, testador ou autor).
     */
    public function getForUser(int $userId, array $filters): Collection
    {
        $query = Task::visible()
            ->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhere('tester_id', $userId)
                  ->orWhere('author_id', $userId);
            })
            ->byPriority();

        $this->applyUserFilters($query, $filters);

        return $query->with([
            'user',
            'author',
            'tester',
            'customer',
            'project',
            'labels',
            'attachments',
        ])->get();
    }

    /**
     * Cria a tarefa em transação e retorna com relações carregadas.
     */
    public function create(array $data): Task
    {
        return DB::transaction(function () use ($data) {
            $task = Task::create($data);

            return $task->load($this->taskRelations);
        });
    }

    /**
     * Sincroniza labels (pivot) de uma task.
     */
    public function syncLabels(Task $task, array $labelIds): void
    {
        $task->labels()->sync($labelIds);
    }

    public function syncRatRecords(Task $task, array $recordIds): void
    {
        $task->ratRecords()->sync($recordIds);
    }

    /**
     * Persiste uma task já instanciada/modificada.
     */
    public function save(Task $task): bool
    {
        return $task->save();
    }

    /**
     * Cria um registro de notificação de task.
     */
    public function createNotification(array $data): void
    {
        Notification::create($data);
    }

    // ── Query Helpers (privados) ───────────────────────────────────────────────

    /**
     * Aplica os filtros da inbox do usuário autenticado.
     */
    private function applyUserFilters(Builder $query, array $filters): void
    {
        $taskId = $this->extractTaskId($filters['task_id'] ?? null);

        if ($taskId !== null) {
            $query->whereKey($taskId);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (!empty($filters['classification'])) {
            $query->where('classification', $filters['classification']);
        }

        $this->applyUserStatusFilter($query, $filters['status'] ?? null);

        $search = $this->resolveSearchTerm($filters);

        if ($search !== null) {
            $this->applyUserSearch($query, $search);
        }
    }

    /**
     * Aplica filtros ao query builder ou Scout builder.
     */
    private function applyFilters($query, array $filters): void
    {
        if (!empty($filters['agent_id'])) {
            $query->where('user_id', (int) $filters['agent_id']);
        }

        if (!empty($filters['customer_id'])) {
            $query->where('customer_id', (int) $filters['customer_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', (int) $filters['project_id']);
        }

        if (!empty($filters['software_id'])) {
            $query->where('software_id', (int) $filters['software_id']);
        }

        if (!empty($filters['label_id'])) {
            $query->whereHas('labels', fn($q) => $q->where('labels.id', (int) $filters['label_id']));
        }

        if (!empty($filters['start'])) {
            $query->whereDate('created_at', '>=', $filters['start']);
        }

        if (!empty($filters['end'])) {
            $query->whereDate('created_at', '<=', $filters['end']);
        }

        if (!empty($filters['delivery_start'])) {
            $query->whereDate('delivery_at', '>=', $filters['delivery_start']);
        }

        if (!empty($filters['delivery_end'])) {
            $query->whereDate('delivery_at', '<=', $filters['delivery_end']);
        }

        if (!empty($filters['bin'])) {
            $query->where('status', 'bin');
        } else {
            $query->visible();
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status'])
                ? $filters['status']
                : explode(',', $filters['status']);
            $query->whereIn('status', $statuses);
        }

        // Ordenação: abertas primeiro, mais recentes no topo
        if (method_exists($query, 'byPriority')) {
            $query->byPriority();
        } elseif (method_exists($query, 'orderByRaw')) {
            $query->orderByRaw("
                CASE status
                    WHEN 'new' THEN 1 WHEN 'pen' THEN 2 WHEN 'pro' THEN 3
                    WHEN 'sto' THEN 4 WHEN 'tdo' THEN 5 WHEN 'don' THEN 6
                    WHEN 'rej' THEN 7 WHEN 'can' THEN 8 WHEN 'bin' THEN 9
                    ELSE 10 END ASC
            ")->orderByDesc('created_at');
        }
    }

    private function applyUserStatusFilter(Builder $query, mixed $statusFilter): void
    {
        if ($statusFilter === null || $statusFilter === '' || $statusFilter === 'all') {
            return;
        }

        if ($statusFilter === 'open') {
            $query->whereIn('status', self::OPEN_STATUSES);

            return;
        }

        if ($statusFilter === 'done') {
            $query->whereNotIn('status', self::OPEN_STATUSES);

            return;
        }

        $statuses = is_array($statusFilter)
            ? $statusFilter
            : array_filter(explode(',', (string) $statusFilter));

        if ($statuses !== []) {
            $query->whereIn('status', $statuses);
        }
    }

    private function applyUserSearch(Builder $query, string $search): void
    {
        $like = '%' . $search . '%';
        $taskIdFromSearch = $this->extractTaskId($search);

        $query->where(function (Builder $builder) use ($like, $taskIdFromSearch) {
            if ($taskIdFromSearch !== null) {
                $builder->whereKey($taskIdFromSearch)
                    ->orWhere(fn (Builder $textQuery) => $this->applyUserTextSearch($textQuery, $like));

                return;
            }

            $this->applyUserTextSearch($builder, $like);
        });
    }

    private function applyUserTextSearch(Builder $query, string $like): void
    {
        $query->where('title', 'like', $like)
            ->orWhere('content', 'like', $like)
            ->orWhereHas('customer', function (Builder $customerQuery) use ($like) {
                $customerQuery->where('trade_name', 'like', $like)
                    ->orWhere('name', 'like', $like);
            })
            ->orWhereHas('project', fn (Builder $projectQuery) => $projectQuery->where('name', 'like', $like))
            ->orWhereHas('user', fn (Builder $userQuery) => $userQuery->where('name', 'like', $like))
            ->orWhereHas('author', fn (Builder $authorQuery) => $authorQuery->where('name', 'like', $like))
            ->orWhereHas('tester', fn (Builder $testerQuery) => $testerQuery->where('name', 'like', $like));
    }

    private function resolveSearchTerm(array $filters): ?string
    {
        $search = $filters['q'] ?? $filters['query'] ?? null;

        if (!is_scalar($search)) {
            return null;
        }

        $normalized = trim((string) $search);

        return $normalized !== '' ? $normalized : null;
    }

    private function extractTaskId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (!is_scalar($value)) {
            return null;
        }

        $normalized = ltrim(trim((string) $value), '#');

        if ($normalized === '' || !ctype_digit($normalized)) {
            return null;
        }

        $taskId = (int) $normalized;

        return $taskId > 0 ? $taskId : null;
    }
}
