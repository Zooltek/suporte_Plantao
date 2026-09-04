<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Notification as HelpdeskNotification;
use Illuminate\Support\Collection;

interface HelpdeskNotificationRepositoryInterface
{
    /**
     * Retorna usuários de acordo com o grupo especificado.
     * Grupos válidos: 'all', 'users', 'agents', 'admins' ou ID numérico.
     */
    public function getUsersByGroup(string $groupId): Collection;

    /**
     * Persiste uma notificação em massa para múltiplos usuários em transação.
     * Invalida o cache de notificações de cada usuário afetado.
     *
     * @param  \Illuminate\Support\Collection  $users
     */
    public function createBulk(\Illuminate\Support\Collection $users, array $data): void;

    /**
     * Retorna as notificações recentes de um usuário com cache.
     */
    public function getRecentForUser(int $userId): Collection;

    /**
     * Marca todas as notificações ativas do usuário como lidas.
     */
    public function markAllAsRead(int $userId): void;
}
