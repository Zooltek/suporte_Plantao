<?php

namespace App\Models\Helpdesk\Ticketit;

use Database\Factories\Helpdesk\Ticketit\StatusFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Status extends Model
{
    use HasFactory;

    /** @var string */
    protected $table = 'ticketit_statuses';

    /** @var array<int, string> */
    protected $fillable = ['name', 'color'];

    /**
     * Indica que esta model não utiliza timestamps.
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relacionamento com Tickets.
     * S6600: Uso de ::class para evitar strings de namespace longas.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    protected static function newFactory()
    {
        return StatusFactory::new();
    }
}
