<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Origin extends Model
{

    use HasFactory;

    protected $table = 'ticketit_origin';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    /**
     * Relacionamento: uma origem pode estar ligada a vários tickets.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
