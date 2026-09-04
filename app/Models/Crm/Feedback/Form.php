<?php

namespace App\Models\Crm\Feedback;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Form extends Model
{

    use HasFactory;

    protected $table = 'crm_feedback_forms';

    /**
     * Indicates that this model should not be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}
