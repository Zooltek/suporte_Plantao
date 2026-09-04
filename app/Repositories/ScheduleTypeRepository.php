<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ScheduleTypeRepositoryInterface;
use App\Models\Schedule;
use App\Models\ScheduleType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ScheduleTypeRepository implements ScheduleTypeRepositoryInterface
{
    private const CACHE_KEY_ACTIVE = 'schedule_types.active';
    private const CACHE_TTL        = 3600;

    public function getAll(): Collection
    {
        return ScheduleType::query()->ordered()->get();
    }

    public function getActive(): Collection
    {
        return Cache::remember(
            self::CACHE_KEY_ACTIVE,
            self::CACHE_TTL,
            fn () => ScheduleType::query()->active()->ordered()->get()
        );
    }

    public function findBySlug(string $slug): ?ScheduleType
    {
        return ScheduleType::query()->where('slug', $slug)->first();
    }

    public function create(array $data): ScheduleType
    {
        $type = ScheduleType::create($data);
        $this->flushCache();

        return $type;
    }

    public function update(ScheduleType $type, array $data): ScheduleType
    {
        $type->update($data);
        $this->flushCache();

        return $type->refresh();
    }

    public function delete(ScheduleType $type): void
    {
        $type->delete();
        $this->flushCache();
    }

    public function hasSchedules(ScheduleType $type): bool
    {
        return Schedule::query()
            ->where(function ($query) use ($type): void {
                $query->where('schedule_type_id', $type->id)
                    ->orWhere('kind', $type->slug);
            })
            ->exists();
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY_ACTIVE);
    }
}
