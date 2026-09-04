<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoletoService extends Model
{
    protected $table = 'service';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relacionamento: um serviço pode estar associado a vários boletos.
     */
    public function boletos(): HasMany
    {
        return $this->hasMany(Boleto::class, 'service_id');
    }
}
