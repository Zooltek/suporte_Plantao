<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'customers';

    public $timestamps = false;

    protected $fillable = [
        'financeiro_id',
        'codigo_empresarial',
        'name',
        'trade_name',
        'cnpj',
        'city_registration',
        'state_registration',
        'customer_group_id',
        'state_id',
        'software_id',
        'contact_name',
        'contact_email',
        'email',
        'phone',
        'telephone_2',
        'address',
        'bairro',
        'city',
        'postal_code',
        'observations',
        'has_ecommerce',
        'has_crm',
        'has_tef',
        'is_active',
        'financial_irregular',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'has_ecommerce' => 'boolean',
        'has_crm' => 'boolean',
        'has_tef' => 'boolean',
        'is_active' => 'boolean',
        'financial_irregular' => 'boolean',
    ];

    protected $searchable = [
        'columns' => [
            'customers.name' => 30,
            'customers.trade_name' => 15,
            'customers.cnpj' => 10,
        ],
    ];

    /** Relações */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(CompanyContact::class, 'customer_id')->orderBy('id');
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function feedbacks(): HasMany
    {
        return $this->hasMany(\App\Models\Crm\Feedback::class);
    }

    public function contract(): HasOne
    {
        return $this->hasOne(Contract::class, 'customer_id');
    }
}
