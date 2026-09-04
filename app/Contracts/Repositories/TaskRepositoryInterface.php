<?php

namespace App\Contracts\Repositories;

use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Collection;

interface TaskRepositoryInterface
{
    /**
     * Busca tarefa pelo ID com todas as relações carregadas.
     */
    public function findWithRelations(int $id): ?Task;

    /**
     * Busca tarefa pelo ID sem relações (para update/destroy). Lança ModelNotFoundException.
     */
    public function findOrFail(int $id): Task;

    /**
     * Retorna coleção de tasks aplicando filtros e ordenação (inclui eager loading).
     *
     * @param  array{query?: string, agent_id?: int, customer_id?: int, project_id?: int,
     *                software_id?: int, label_id?: int, start?: string, end?: string,
     *                delivery_start?: string, delivery_end?: string, bin?: bool,
     *                status?: string|array, limite?: int}  $filters
     */
    public function getFiltered(array $filters): Collection;

    /**
     * Retorna tasks do usuário (responsável, testador ou autor), com filtros opcionais.
     *
     * @param  array{user_id?: int, q?: string, query?: string, task_id?: int,
     *                status?: string|array, classification?: string,
     *                customer_id?: int, project_id?: int}  $filters
     */
    public function getForUser(int $userId, array $filters): Collection;

    /**
     * Cria a tarefa e retorna carregada com as relações padrão.
     */
    public function create(array $data): Task;

    /**
     * Sincroniza labels (pivot) de uma task.
     */
    public function syncLabels(Task $task, array $labelIds): void;

    /**
     * Sincroniza os RATs vinculados à task.
     */
    public function syncRatRecords(Task $task, array $recordIds): void;

    /**
     * Persiste uma task já instanciada/modificada.
     */
    public function save(Task $task): bool;

    /**
     * Cria um registro de notificação de task.
     */
    public function createNotification(array $data): void;
}
