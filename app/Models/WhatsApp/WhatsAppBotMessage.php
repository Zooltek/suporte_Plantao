<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppBotMessage extends Model
{
    protected $table = 'whatsapp_bot_messages';

    protected $fillable = [
        'key',
        'step',
        'text',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
}
