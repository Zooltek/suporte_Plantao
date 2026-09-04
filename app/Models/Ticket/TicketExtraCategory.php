<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketExtraCategory extends Model
{
    protected $table = 'ticket_extra_categories';

    protected $fillable = [
        'ticket_id',
        'category_id',
        'sub_category_id',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class, 'category_id', 'category_id');
    }

    public function subCategory(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Category::class, 'sub_category_id', 'category_id');
    }
}
