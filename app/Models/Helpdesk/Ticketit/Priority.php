<?php

namespace App\Models\Helpdesk\Ticketit;

use Database\Factories\Helpdesk\Ticketit\PriorityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Priority extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'ticketit_priorities';

    /** @var array<int, string> */
    protected $fillable = ['name', 'color'];

    /**
     * Indica que esta model não utiliza timestamps.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relacionamento com Tickets.
     * S6600: Uso de ::class para maior clareza e suporte da IDE.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    protected static function newFactory()
    {
        return PriorityFactory::new();
    }
}
