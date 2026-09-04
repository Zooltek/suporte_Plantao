<?php

namespace App\Models\Helpdesk\Ticketit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use App\Http\Traits\ContentEllipse;
use App\Http\Traits\Purifiable;

class Comment extends Model
{
    use ContentEllipse;
    use Purifiable;

    /** @var string */
    protected $table = 'ticketit_comments';

    /**
     * Relacionamento com o Ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Relacionamento com o Autor do comentário.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Token para abrir comentários na Timeline.
     * S1142: Retorno direto e único.
     */
    public function getOpenToken(): string
    {
        return "436934F5421A32188766E61B9F91B";
    }

    /**
     * Token para fechar comentários na Timeline.
     */
    public function getCloseToken(): string
    {
        return "82B2E51DBDBDDBE288E743511C191";
    }
}
