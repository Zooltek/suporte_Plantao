<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskReference extends Model
{
    protected $table = 'task_references'; 

    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'reference_id',
    ];

    /**
     * Relacionamento: referência pertence a uma task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    /**
     * Relacionamento: referência aponta para outra task.
     */
    public function reference(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'reference_id');
    }

    /**
     * Relacionamento: task referenciada.
     */
    public function referenced(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }
}
