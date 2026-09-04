<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Models\Tasks\Notification;
use Illuminate\Database\Eloquent\Collection;

class NotificationRepository implements NotificationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $data): Notification
    {
        return Notification::create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function getForUser(int $userId, bool $onlyActive): Collection
    {
        $query = Notification::query()
            ->where('user_id', $userId)
            ->with('task')
            ->orderByDesc('created_at')
            ->take(8);

        if ($onlyActive) {
            $query->where('seen', 0)
                  ->where('created_at', '>', now()->subHour());
        }

        return $query->get();
    }

    /**
     * {@inheritDoc}
     */
    public function findForUser(int $id, int $userId): Notification
    {
        return Notification::where('user_id', $userId)->findOrFail($id);
    }

    /**
     * {@inheritDoc}
     */
    public function markSeen(Notification $notification): bool
    {
        return $notification->update(['seen' => 1]);
    }
}
