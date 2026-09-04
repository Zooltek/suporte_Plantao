<?php

namespace App\Models\WhatsApp;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessage extends Model
{
    use HasFactory;

    protected static function newFactory(): \Database\Factories\WhatsApp\WhatsAppMessageFactory
    {
        return \Database\Factories\WhatsApp\WhatsAppMessageFactory::new();
    }

    protected $table = 'whatsapp_messages';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'direction',
        'type',
        'body',
        'attachment_path',
        'original_filename',
        'mime_type',
        'file_size',
        'provider_message_id',
        'is_internal',
        'deleted_by',
        'deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_internal' => 'boolean',
            'deleted_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function isInbound(): bool
    {
        return $this->direction === 'inbound';
    }

    public function isMedia(): bool
    {
        return in_array($this->type, ['image', 'document', 'audio', 'video'], true);
    }

    public function isDeleted(): bool
    {
        return $this->deleted_at !== null;
    }
}
