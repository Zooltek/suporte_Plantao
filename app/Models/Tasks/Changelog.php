<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Changelog extends Model
{
    protected $table = 'tasks_changelogs';

    public $timestamps = true;

    protected $fillable = [
        'content',
        'title',
        'description',
        'status',
        'sort_order',
        'user_id',
        'task_id',
        'project_id',
        'version_id',
        'bold',
        'blank',
        'color',
    ];

    /**
     * Escopo: apenas changelogs ativos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'can');
    }

    /**
     * Relacionamento: changelog pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
