<?php

namespace App\Models\Helpdesk\Ticket;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\Helpdesk\Ticket;
use App\Models\Helpdesk\Ticket\Agent;

class Category extends Model
{
    protected $table = 'ticketit_categories';

    protected $fillable = ['name', 'color'];

    /**
     * Indicates that this model should not be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Get related tickets.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'category_id');
    }

    /**
     * Get related agents.
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(
            Agent::class,
            'ticketit_categories_users',
            'category_id',
            'user_id'
        );
    }
}
