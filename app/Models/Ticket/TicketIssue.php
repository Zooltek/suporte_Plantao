<?php

namespace App\Models\Ticket;

use App\Models\User;
use Database\Factories\Ticket\TicketIssueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketIssue extends Model
{
    use HasFactory;


    protected $table = 'ticket_issues';

    protected $fillable = [
        'ticket_id',
        'created_by',
        'title',
        'description',
        'solution',
        'status',
        'resolved_at',
        'resolved_by',
    ];

    protected function casts(): array
    {
        return [
            'resolved_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    protected static function newFactory(): TicketIssueFactory
    {
        return TicketIssueFactory::new();
    }
}
