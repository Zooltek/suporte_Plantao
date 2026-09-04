<?php

namespace App\Models\WhatsApp;

use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppMessageMacro extends Model
{
    protected $table = 'whatsapp_message_macros';

    protected $fillable = [
        'command',
        'text',
        'department_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
