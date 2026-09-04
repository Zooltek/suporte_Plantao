<?php

namespace App\Events\Tasks;

use App\Models\Tasks\Task;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusChanged implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Task $task,
        public readonly string $previousStatus,
        public readonly int $changedBy
    ) {}

    /**
     * Notifica cada participante da task em seu canal privado.
     */
    public function broadcastOn(): array
    {
        $participants = array_unique(array_filter([
            $this->task->author_id,
            $this->task->user_id,
            $this->task->tester_id,
        ]));

        return array_map(
            fn ($userId) => new PrivateChannel("tasks.user.{$userId}"),
            $participants
        );
    }

    public function broadcastAs(): string
    {
        return 'task.status.changed';
    }

    public function broadcastWith(): array
    {
        $statusLabels = [
            'new' => 'Nova', 'pen' => 'Pendente', 'pro' => 'Em andamento',
            'don' => 'Concluída', 'tdo' => 'Concluída (TI)', 'sto' => 'Parada',
            'can' => 'Cancelada', 'rej' => 'Rejeitada', 'bin' => 'Excluída',
        ];

        return [
            'task_id'         => $this->task->id,
            'task_title'      => $this->task->title,
            'previous_status' => $this->previousStatus,
            'new_status'      => $this->task->status,
            'status_label'    => $statusLabels[$this->task->status] ?? $this->task->status,
            'changed_by'      => $this->changedBy,
            'changed_at'      => now()->toISOString(),
        ];
    }
}
