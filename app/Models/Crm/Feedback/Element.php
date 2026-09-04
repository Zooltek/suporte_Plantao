<?php

namespace App\Models\Crm\Feedback;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Element extends Model
{
    use HasFactory;

    protected $table = 'crm_feedback_element';

    protected $fillable = [
        'feedback_id',
        'element_id',
        'value',
    ];

    /**
     * Indicates that this model should not be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function type() {
        return $this->belongsTo(\App\Models\Crm\Feedback\ElementType::class, 'element_id');
    }
}
