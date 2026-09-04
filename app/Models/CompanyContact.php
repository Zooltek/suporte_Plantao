<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyContact extends Model
{
    protected $table = 'customer_contacts';

    protected $fillable = [
        'customer_id',
        'name',
        'phone',
        'email',
        'origin',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'customer_id');
    }
}
