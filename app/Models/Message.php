<?php

namespace App\Models;

use App\Models\Helpdesk\Chat\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $table = 'chat_messages';

    public $timestamps = true;

    protected $fillable = [
        'chat_id',
        'user_id',
        'content',
    ];

    /**
     * Relacionamento: mensagem pertence a um usuário (owner).
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relacionamento: mensagem pertence a uma conversa.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class, 'chat_id');
    }
}
