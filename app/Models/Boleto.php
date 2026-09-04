<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boleto extends Model
{
    protected $table = 'sales_order';

    public $timestamps = false;

    protected $fillable = [
        'customer_id',
        'service_id',
        'status_id',
        'due_date',
        'amount',
    ];

    protected $casts = [
        'due_date' => 'datetime',
    ];

    /**
     * Relacionamento: boleto pertence a um status.
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(BoletoStatus::class, 'status_id');
    }

    /**
     * Relacionamento: boleto pertence a um serviço.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(BoletoService::class, 'service_id');
    }
}
