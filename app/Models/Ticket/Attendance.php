<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Database\Factories\Ticket\AttendanceFactory;

class Attendance extends Model
{
    use HasFactory;


    protected $table = 'attendances';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'notes',
        'return_zap',
        'return_tel',
        'return_cel',
        'return_assigned_to',
        'return_scheduled_at',
        'returned_by',
        'returned_at',
    ];

    protected $casts = [
        'return_zap'  => 'boolean',
        'return_tel'  => 'boolean',
        'return_cel'  => 'boolean',
        'return_scheduled_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function returnedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function returnAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'return_assigned_to');
    }

    public function hasReturn(): bool
    {
        return $this->return_zap || $this->return_tel || $this->return_cel;
    }

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }
}
