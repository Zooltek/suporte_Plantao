<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Software extends Model
{
    use HasFactory;
    
    protected $table = 'softwares'; 

    public $timestamps = false;

    protected $fillable = [
        'name',
        'version',
        'status',
    ];

    /**
     * Relacionamento: software pode ter vários clientes.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Relacionamento: software pode ter várias tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(\App\Models\Tasks\Task::class);
    }
}
