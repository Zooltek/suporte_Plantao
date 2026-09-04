<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'task_attachments'; 

    public $timestamps = true;

    protected $fillable = [
        'task_id',
        'file_path',
        'file_name',
        'mime_type',
        'size',
    ];

    /**
     * Relacionamento: attachment pertence a uma task.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
