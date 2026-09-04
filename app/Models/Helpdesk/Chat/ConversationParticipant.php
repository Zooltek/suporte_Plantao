<?php

namespace App\Models\Helpdesk\Chat;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Models\Helpdesk\Chat\Conversation;

class ConversationParticipant extends Model
{
    protected $table = 'chat_participants';

    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'socket_id',
        'session',
        'display_name',
        'email',
        'token',
    ];

    /**
     * Relacionamento: participante pertence a um usuário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: participante pertence a uma conversa.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'conversation_id');
    }
}
