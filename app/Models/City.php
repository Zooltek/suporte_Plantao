<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $table = 'cities'; 

    public $timestamps = false;

    protected $fillable = [
        'name',
        'state_id',
    ];

    /**
     * Relacionamento: cidade pertence a um estado.
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class, 'state_id');
    }

    /**
     * Relacionamento: cidade pode ter vários clientes.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'city_id');
    }
}
