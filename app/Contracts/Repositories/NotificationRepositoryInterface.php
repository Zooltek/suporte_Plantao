<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tasks\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Cria e persiste uma nova notificação.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Notification;

    /**
     * Retorna as notificações de um usuário, com eager-loading da task.
     * Quando $onlyActive = true, filtra apenas não-vistas nas últimas 1h.
     */
    public function getForUser(int $userId, bool $onlyActive): Collection;

    /**
     * Busca uma notificação pelo id e user_id; lança ModelNotFoundException se não encontrada.
     */
    public function findForUser(int $id, int $userId): Notification;

    /**
     * Marca a notificação como lida (seen = 1).
     */
    public function markSeen(Notification $notification): bool;
}
