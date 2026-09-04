<?php

namespace App\Models\Tasks;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class UserProject extends Model
{
    protected $table = 'tasks_users_projects'; 

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'project_id',
    ];

    /**
     * Relacionamento: vínculo pertence a um projeto.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Relacionamento: vínculo pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
