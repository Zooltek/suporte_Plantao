<?php

namespace App\Models\Ticket;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Priority extends Model
{
    use HasFactory;

    protected $table = 'ticketit_priorities';

    protected $fillable = ['name', 'color'];

    public $timestamps = false;

    protected static function newFactory()
    {
        return \Database\Factories\Ticket\PriorityFactory::new();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }
}
