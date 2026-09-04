<?php

namespace App\Models\Ticket;

use App\Models\User;
use Database\Factories\Ticket\CommentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Ticket\ContentEllipse;
use App\Traits\Ticket\Purifiable;

class Comment extends Model
{
    use HasFactory;
    use ContentEllipse;
    use Purifiable;

    protected $table = 'ticketit_comments';

    protected $fillable = ['content', 'html', 'user_id', 'ticket_id'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getOpenToken(): string
    {
        return "436934F5421A32188766E61B9F91B";
    }

    public function getCloseToken(): string
    {
        return "82B2E51DBDBDDBE288E743511C191";
    }

    protected static function newFactory(): CommentFactory
    {
        return CommentFactory::new();
    }
}
