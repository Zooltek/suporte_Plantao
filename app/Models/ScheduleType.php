<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Tipo configurável de agendamento (slug + label + regra de exigência de cliente).
 *
 * @property int $id
 * @property string $slug
 * @property string $label
 * @property bool $requires_customer
 * @property bool $is_system
 * @property bool $is_active
 * @property int $sort_order
 */
class ScheduleType extends Model
{
    use HasFactory;

    protected $table = 'schedule_types';

    protected $fillable = [
        'slug',
        'label',
        'requires_customer',
        'is_system',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'requires_customer' => 'boolean',
        'is_system'         => 'boolean',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class, 'schedule_type_id');
    }
}
