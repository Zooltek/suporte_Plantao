<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attachment extends Model
{
    protected $table = 'ticketit_attachments';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'original_name',
        'disk_path',
        'size',
        'mime',
        'author_id',
        'ticket_id',
        'status',
    ];

    /**
     * Relacionamento: attachment pertence a um ticket.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * URL pública do attachment.
     */
    public function getAttachmentUrl(): string
    {
        return url("/ticket/attachment/{$this->id}.{$this->mime}");
    }

    /**
     * URL para download do attachment.
     */
    public function getAttachmentDownloadUrl(): string
    {
        return url("/ticket/attachment/{$this->id}/download");
    }
}
