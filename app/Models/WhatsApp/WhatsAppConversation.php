<?php

namespace App\Models\WhatsApp;

use App\Enums\WhatsApp\ConversationState;
use App\Models\Company;
use App\Models\Ticket\Ticket;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppConversation extends Model
{
    use HasFactory;

    protected static function newFactory(): \Database\Factories\WhatsApp\WhatsAppConversationFactory
    {
        return \Database\Factories\WhatsApp\WhatsAppConversationFactory::new();
    }

    protected $table = 'whatsapp_conversations';

    protected $fillable = [
        'phone',
        'state',
        'payload',
        'company_id',
        'ticket_id',
        'last_activity_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'state' => ConversationState::class,
            'payload' => 'array',
            'last_activity_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public function isActive(): bool
    {
        return ! $this->state->isTerminal();
    }

    public function isExpired(): bool
    {
        if ($this->state === ConversationState::HUMAN_PENDING && $this->ticket_id) {
            $ticket = $this->ticket ?? Ticket::find($this->ticket_id);
            if ($ticket && $ticket->completed_at === null && ! \App\Models\Ticket\Status::isTerminal($ticket->status_id)) {
                return false;
            }

            try {
                if (app(\App\Services\WhatsApp\WhatsAppBotReleaseService::class)->isWithinTicketClosedDelay($this)) {
                    return false;
                }
            } catch (\Throwable) {
                // fallback seguro
            }
        }

        $ttl = config('whatsapp.chatbot.session_ttl_minutes', 60);

        return $this->last_activity_at?->copy()->addMinutes($ttl)->isPast() ?? true;
    }

    public function getPayloadValue(string $key, mixed $default = null): mixed
    {
        return data_get($this->payload ?? [], $key, $default);
    }

    public function mergePayload(array $data): void
    {
        $this->payload = array_merge($this->payload ?? [], $data);
    }

    public function touch($attribute = null): bool
    {
        $this->last_activity_at = now();

        return parent::touch($attribute);
    }
}
