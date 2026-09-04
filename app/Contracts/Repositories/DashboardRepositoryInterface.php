<?php

namespace App\Contracts\Repositories;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

interface DashboardRepositoryInterface
{
    public function getCustomers(): Collection;

    public function countSchedules(?int $agentId, string $type): int;

    public function getRecentAttendances(?int $agentId): Collection;

    public function countTodayAttendances(?int $agentId): int;

    public function getRecentTickets(?int $agentId, ?int $departmentId = null, bool $isAdmin = true): Collection;

    public function getCategoryNames(): Collection;

    public function getSchedulesForWeek(Carbon $startOfWeek, Carbon $endOfWeek, ?int $agentId): Collection;

    public function countMyTasks(int $userId, string $status): int;

    public function countMyTasksDueToday(int $userId): int;

    public function countUnseenTaskNotifications(int $userId): int;

    public function getUserSettings(int $userId): array;
}
