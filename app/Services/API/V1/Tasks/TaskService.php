<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Events\Tasks\TaskStatusChanged;
use App\Models\Tasks\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Exception;

class TaskService
{
    private const OPEN_STATUSES = ['new', 'pen', 'pro', 'sto'];

    public function __construct(
        private readonly TaskRepositoryInterface $taskRepository,
    ) {}

    public function getTasks(array $filters, ?int $id = null)
    {
        if ($id) {
            return $this->taskRepository->findWithRelations($id);
        }

        $tasks = $this->taskRepository->getFiltered($filters);

        $this->appendMetadata($tasks);

        return $tasks;
    }

    /**
     * Todas as tasks vinculadas ao usuário autenticado (responsável, testador ou autor),
     * abertas primeiro, mais recentes no topo.
     */
    public function getMyTasks(array $filters = []): Collection
    {
        $userId = $filters['user_id'] ?? (auth('admin')->id() ?? Auth::id());

        $tasks = $this->taskRepository->getForUser((int) $userId, $filters);

        $this->appendMetadata($tasks);

        return $tasks;
    }

    public function createTask(array $data): Task
    {
        $payload = [
            'title'          => html_entity_decode(strip_tags($data['title'])),
            'content'        => $data['content'] ?? null,
            'user_id'        => $data['user_id'] ?? Auth::id(),
            'author_id'      => $data['author_id'] ?? Auth::id(),
            'parent_id'      => $data['parent_id'] ?? null,
            'project_id'     => $data['project_id'] ?? null,
            'customer_id'    => $data['customer_id'] ?? null,
            'software_id'    => !empty($data['software_id']) ? (int) $data['software_id'] : null,
            'status'         => $data['status'] ?? 'pen',
            'classification' => $data['classification'] ?? null,
            'request_at'     => $this->parseDateInput($data['request_at'] ?? null),
            'delivery_at'    => $this->parseDateInput($data['delivery_at'] ?? null)?->startOfDay(),
        ];

        $task = $this->taskRepository->create($payload);

        $this->syncRelations($task, $data);
        $this->notifyParticipants($task, 'nova tarefa');

        // Reload após sync de relações para retornar estado atualizado
        return $this->taskRepository->findWithRelations($task->id);
    }

    public function updateTaskStatus(int $id, array $data): bool
    {
        $task = $this->taskRepository->findOrFail($id);

        if (!$task->canModify()) {
            throw new Exception('Não autorizado', 403);
        }

        $previousStatus = $task->status;

        $task->fill(array_intersect_key($data, array_flip(['status', 'description'])));

        if (($data['status'] ?? '') === 'pro' && !$task->started_at) {
            $task->started_at = now();
        }

        if (in_array($data['status'] ?? '', ['don', 'tdo'], true)) {
            $task->completed_at = now();
        }

        $this->taskRepository->save($task);
        $this->notifyStatusChange($task);

        event(new TaskStatusChanged($task, $previousStatus, (int) (auth('admin')->id() ?? Auth::id() ?? 0)));

        return true;
    }

    public function updateTask(int $id, array $data): Task
    {
        $task = $this->taskRepository->findOrFail($id);

        if (!$task->canModify()) {
            throw new Exception('Não autorizado', 403);
        }

        $previousStatus = $task->status;

        $task->fill([
            'title' => html_entity_decode(strip_tags($data['title'])),
            'content' => $data['content'],
            'user_id' => (int) $data['user_id'],
            'customer_id' => !empty($data['customer_id']) ? (int) $data['customer_id'] : null,
            'classification' => $data['classification'] ?? null,
            'status' => $data['status'],
            'delivery_at' => $this->parseDateInput($data['delivery_at'] ?? null)?->startOfDay(),
        ]);

        if (($data['status'] ?? '') === 'pro' && !$task->started_at) {
            $task->started_at = now();
        }

        if (in_array($data['status'] ?? '', ['don', 'tdo'], true)) {
            $task->completed_at = now();
        }

        $this->taskRepository->save($task);
        $this->syncRelations($task, $data);

        if ($previousStatus !== $task->status) {
            $this->notifyStatusChange($task);

            event(new TaskStatusChanged($task, $previousStatus, (int) (auth('admin')->id() ?? Auth::id() ?? 0)));
        }

        return $this->taskRepository->findWithRelations($task->id) ?? $task;
    }

    public function destroyTask(int $id): bool
    {
        $task = $this->taskRepository->findOrFail($id);

        if (!$task->canModify()) {
            throw new Exception('Não autorizado', 403);
        }

        $task->status = ($task->status !== 'can') ? 'can' : 'bin';
        $this->taskRepository->save($task);

        return true;
    }

    // ── Private (regras de negócio) ────────────────────────────────────────────

    /**
     * Aceita d/m/Y (form PT-BR) e Y-m-d (input type=date), devolve Carbon ou null.
     * Entrada inválida resulta em null — a validação já barra formatos em FormRequest.
     */
    private function parseDateInput(null|string|Carbon $input): ?Carbon
    {
        if ($input instanceof Carbon) {
            return $input;
        }

        $value = is_string($input) ? trim($input) : '';
        if ($value === '') {
            return null;
        }

        foreach (['d/m/Y', 'Y-m-d', 'Y-m-d H:i:s', 'd/m/Y H:i'] as $format) {
            $parsed = Carbon::createFromFormat($format, $value);
            if ($parsed !== false && $parsed->format($format) === $value) {
                return $parsed;
            }
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function appendMetadata(Collection $tasks): void
    {
        $tasks->each(function (Task $task) {
            $task->is_open    = in_array($task->status, self::OPEN_STATUSES, true);
            $task->can_modify = $task->canModify();
        });
    }

    private function syncRelations(Task $task, array $data): void
    {
        if (!empty($data['labels']) && is_array($data['labels'])) {
            $this->taskRepository->syncLabels($task, $data['labels']);
        }

        if (array_key_exists('rat_record_ids', $data) && is_array($data['rat_record_ids'])) {
            $this->taskRepository->syncRatRecords($task, $data['rat_record_ids']);
        }

        if (!empty($data['tester_id'])) {
            $task->tester_id = (int) $data['tester_id'];
            $this->taskRepository->save($task);
        }
    }

    private function notifyParticipants(Task $task, string $type): void
    {
        $participants = array_unique(array_filter([$task->user_id, $task->tester_id]));

        foreach ($participants as $userId) {
            if ((int) $userId === (int) ($task->author_id ?? 0)) {
                continue;
            }

            $this->taskRepository->createNotification([
                'ref_id'    => $task->id,
                'user_id'   => $userId,
                'author_id' => $task->author_id,
                'content'   => "Nova tarefa atribuída: {$task->title}",
                'kind'      => 'new_task',
                'status'    => 0,
                'seen'      => 0,
            ]);
        }
    }

    private function notifyStatusChange(Task $task): void
    {
        $participants = array_unique(array_filter([
            $task->author_id, $task->user_id, $task->tester_id,
        ]));

        $labels = [
            'new' => 'Nova', 'pen' => 'Pendente', 'pro' => 'Em andamento',
            'don' => 'Concluída', 'tdo' => 'Concluída (TI)', 'sto' => 'Parada',
            'can' => 'Cancelada', 'rej' => 'Rejeitada',
        ];
        $label = $labels[$task->status] ?? $task->status;

        $currentUserId = auth('admin')->id() ?? Auth::id();

        foreach ($participants as $userId) {
            if ((int) $userId === (int) $currentUserId) {
                continue;
            }

            $this->taskRepository->createNotification([
                'ref_id'    => $task->id,
                'user_id'   => $userId,
                'author_id' => $task->author_id,
                'content'   => "Tarefa \"{$task->title}\" alterada para: {$label}",
                'kind'      => 'status_change',
                'status'    => 0,
                'seen'      => 0,
            ]);
        }
    }
}
