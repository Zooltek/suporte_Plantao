<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    protected $table = 'task_comments'; 

    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'author_id',
        'content',
        'status',
    ];

    /**
     * Escopo: apenas comentários ativos.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', 'can');
    }

    /**
     * Relacionamento: comentário pertence a uma task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
