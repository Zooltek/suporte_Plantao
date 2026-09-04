<?php

namespace App\Models\Helpdesk\Chat;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\User;
use App\Models\Ticket\Ticket;

class Conversation extends Model
{
    use HasFactory;

    protected $table = 'chats';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'desc',
        'session',
        'password',
        'owner_id',
        'agent_id',
        'status_id',
        'expire_at',
        'closed_at',
        'subject',
        'ticket_id',
    ];

    protected $casts = [
        'expire_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(ConversationStatus::class, 'status_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'chat_id')->orderBy('created_at');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class, 'conversation_id');
    }

    public function isClosed(): bool
    {
        return (int) $this->status_id === 4;
    }
}
