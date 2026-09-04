<?php

namespace App\Models\WhatsApp;

use Illuminate\Database\Eloquent\Model;

class WhatsAppSetting extends Model
{
    protected $table = 'whatsapp_settings';

    protected $fillable = [
        'key',
        'value',
    ];
}
