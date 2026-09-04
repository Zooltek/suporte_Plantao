<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HelpdeskNotificationRepositoryInterface;
use App\Models\Notification as HelpdeskNotification;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HelpdeskNotificationRepository implements HelpdeskNotificationRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function getUsersByGroup(string $groupId): Collection
    {
        return match ($groupId) {
            'all'    => User::all(),
            'users'  => User::where('ticketit_agent', 0)->where('ticketit_admin', 0)->get(),
            'agents' => User::where('ticketit_agent', 1)->get(),
            'admins' => User::where('ticketit_admin', 1)->get(),
            default  => User::where('id', $groupId)->get(),
        };
    }

    /**
     * {@inheritDoc}
     */
    public function createBulk(Collection $users, array $data): void
    {
        DB::transaction(function () use ($users, $data) {
            foreach ($users as $user) {
                HelpdeskNotification::create([
                    'user_id' => $user->id,
                    'content' => $data['content'],
                    'action'  => $data['action'] ?? null,
                    'image'   => $data['image'] ?? null,
                    'status'  => 1,
                ]);
            }
        });

        foreach ($users as $user) {
            Cache::forget("user_recent_notifications_{$user->id}");
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getRecentForUser(int $userId): Collection
    {
        return Cache::remember("user_recent_notifications_{$userId}", 60, function () use ($userId) {
            return HelpdeskNotification::where('user_id', $userId)
                ->orderBy('updated_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($notification) {
                    $notification->time_ago = $notification->updated_at->diffForHumans();
                    return $notification;
                });
        });
    }

    /**
     * {@inheritDoc}
     */
    public function markAllAsRead(int $userId): void
    {
        HelpdeskNotification::where('user_id', $userId)
            ->where('status', 1)
            ->update(['status' => 0]);

        Cache::forget("user_recent_notifications_{$userId}");
    }
}
