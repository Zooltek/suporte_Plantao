<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoletoStatus extends Model
{
    protected $table = 'sales_order_status';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relacionamento: um status pode estar associado a vários boletos.
     */
    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class, 'status_id');
    }
}
