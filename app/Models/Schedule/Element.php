<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Element extends Model
{
    protected $table = 'schedule_record_element'; 

    public $timestamps = false;

    protected $fillable = [
        'element_id',
        'record_id',
        'value',
    ];

    /**
     * Relacionamento: cada Element pertence a um tipo.
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ElementType::class, 'element_id');
    }
}
