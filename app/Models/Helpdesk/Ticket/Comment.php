<?php

namespace App\Models\Helpdesk\Ticket;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Helpdesk\Ticket;
use App\Models\User;
use Kordy\Ticketit\Traits\ContentEllipse;
use Kordy\Ticketit\Traits\Purifiable;

class Comment extends Model
{
    //use ContentEllipse, Purifiable;

    protected $table = 'ticketit_comments';

    /**
     * Get related ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get comment owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Token to Close/Open comments in Timeline
     * WARNING: Do not change this!
     */
    public function getOpenToken(): string
    {
        return "436934F5421A32188766E61B9F91B";
    }

    public function getCloseToken(): string
    {
        return "82B2E51DBDBDDBE288E743511C191";
    }
}
