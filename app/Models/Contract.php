<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelLang\Publisher\Concerns\Has;

class Contract extends Model
{

    use HasFactory;
    /**
     * Nome da tabela associada ao modelo.
     *
     * @var string
     */
    protected $table = 'customer_contract';

    /**
     * Indica que este modelo não deve usar timestamps.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Relacionamento com o modelo Customer.
     *
     * @return BelongsTo
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
