<?php

namespace App\Models\Tasks;

use App\Models\Department;
use App\Models\Schedule\Module as ScheduleModule;
use Database\Factories\Tasks\LabelFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Label extends Model
{
    use HasFactory;

    protected $table = 'labels';

    protected $fillable = [
        'name',
        'color',
        'is_active',
        'parent_id',
        'user_id',
        'department_id',
        'schedule_module_id',
    ];

    public $timestamps = true;

    /**
     * Escopo: apenas labels ativos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function childs(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_active', true);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'tasks_project_modules', 'label_id', 'project_id');
    }

    public function scheduleModule(): BelongsTo
    {
        return $this->belongsTo(ScheduleModule::class, 'schedule_module_id');
    }

    protected static function newFactory()
    {
        return LabelFactory::new();
    }
}
