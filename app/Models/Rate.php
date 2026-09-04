<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rate extends Model
{
    protected $table = 'ticketit_agent_rate';

    public $timestamps = false;

    protected $fillable = [
        'agent_id',
        'rate',
    ];

    /**
     * Relacionamento: avaliação pertence a um usuário/agente.
     */
    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
