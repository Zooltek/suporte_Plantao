<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerGroup extends Model
{
    use HasFactory;

    protected $table = 'customer_groups';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'name',
        'financial_code',
        'hash',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Relacionamento: grupo possui vários clientes.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'customer_group_id');
    }
}
