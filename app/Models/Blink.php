<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class Blink extends Model
{
    protected $table = 'user_blink'; 

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'hash',
        'status',
    ];

    /**
     * Relacionamento: cada Blink pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
