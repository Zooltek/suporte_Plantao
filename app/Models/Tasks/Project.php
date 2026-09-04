<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $table = 'tasks_projects';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'schedule_project_name',
        'description',
        'status',
        'color',
        'user_id',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    /**
     * Escopo: apenas projetos ativos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 1);
    }

    /**
     * Relacionamento: projeto possui várias tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Módulos raiz disponíveis para classificar tarefas deste projeto.
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'tasks_project_modules', 'project_id', 'label_id');
    }
}
