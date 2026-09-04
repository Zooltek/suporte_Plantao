<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'tasks_notification';

    public $timestamps = true;

    protected $fillable = [
        'content',
        'ref_id',
        'kind',
        'author_id',
        'user_id',
        'status',
        'seen',
    ];

    /**
     * Relacionamento: notificação pertence a uma task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'ref_id');
    }

    /**
     * Relacionamento: notificação pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }
}
