<?php

namespace App\Models\Helpdesk\Chat;

use App\Models\Helpdesk\Chat\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationStatus extends Model
{
    protected $table = 'chat_statuses';

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Relacionamento: um status pode estar associado a várias conversas.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'status_id');
    }
}
