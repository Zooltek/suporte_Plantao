<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    use HasFactory;

    protected $table = 'states';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'abbreviation',
    ];

    /**
     * Accessor: atalho para abbreviation usado nas views.
     */
    public function getAbbrAttribute(): ?string
    {
        return $this->abbreviation;
    }

    /**
     * Relacionamento: um estado pode ter vários clientes.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }
}
