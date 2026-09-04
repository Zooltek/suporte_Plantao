<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Jobs\Tasks\PushNotification;
use App\Models\Tasks\Notification;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class NotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notificationRepository,
    ) {}

    /**
     * Cria uma nova notificação e dispara o Job de push.
     * Incorporado do legado App\Services\Tasks\NotificationService.
     */
    public function createNotification(array $data): Notification
    {
        $notification = $this->notificationRepository->create([
            'ref_id'    => $data['ref_id'],
            'content'   => $data['content'],
            'kind'      => $data['kind'],
            'author_id' => $data['author_id'],
            'user_id'   => $data['user_id'],
            'icon'      => $this->getNotificationIcon($data['status'] ?? ''),
        ]);

        PushNotification::dispatch($notification)
            ->delay(now()->addSeconds(2));

        return $notification;
    }

    /**
     * Retorna a lista de notificações do usuário, formatadas e opcionalmente filtradas.
     */
    public function getNotifications(int $userId, bool $isActive): Collection
    {
        Carbon::setLocale('pt_BR');

        $notifications = $this->notificationRepository->getForUser($userId, $isActive);

        $notifications->each(function ($notification) {
            $notification->icon      = $this->getNotificationIcon($notification->task?->status);
            $notification->url       = 'ae';
            $notification->timestamp = $notification->created_at->diffForHumans();
        });

        return $notifications;
    }

    /**
     * Marca uma notificação específica como lida.
     */
    public function markAsSeen(int $id, int $userId): bool
    {
        $notification = $this->notificationRepository->findForUser($id, $userId);

        if ($notification->seen) {
            throw new Exception('Already seen', 422);
        }

        return $this->notificationRepository->markSeen($notification);
    }

    /**
     * Retorna o ícone correspondente ao status da tarefa.
     */
    private function getNotificationIcon(?string $status): string
    {
        return match ($status) {
            'don'        => 'task-done.png',
            'can', 'rej' => 'task-remove.png',
            'pen'        => 'task-new.png',
            default      => 'task-default.png',
        };
    }
}
