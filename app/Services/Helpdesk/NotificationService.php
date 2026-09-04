<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\HelpdeskNotificationRepositoryInterface;
use Illuminate\Support\Collection;

class NotificationService
{
    public function __construct(
        private readonly HelpdeskNotificationRepositoryInterface $repository,
    ) {}

    /**
     * Envia notificações em massa para grupos específicos.
     */
    public function sendToGroup(string $groupId, array $data): int
    {
        $users = $this->repository->getUsersByGroup($groupId);

        $this->repository->createBulk($users, $data);

        return $users->count();
    }

    /**
     * Busca notificações recentes com cache Redis para o "blink".
     */
    public function getRecentForUser(int $userId): Collection
    {
        return $this->repository->getRecentForUser($userId);
    }

    /**
     * Marca todas como lidas.
     */
    public function markAllAsRead(int $userId): void
    {
        $this->repository->markAllAsRead($userId);
    }
}
