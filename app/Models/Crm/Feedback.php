<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Crm\Feedback\Form;
use App\Models\User;
use App\Models\Customer;
use App\Models\Crm\Feedback\Element;
use App\Models\Crm\Feedback\ElementType;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'crm_feedback';

    protected $casts = [
        'completed_at' => 'datetime',
        'return_at' => 'datetime',
        'return_author_id' => 'integer',
        'form_id' => 'integer',
        'customer_id' => 'integer',
        'user_id' => 'integer',
    ];

    protected $fillable = [
        'content',
        'suggestions',
        'complaint',
        'cancel_content',
        'return_content',
        'return_author_id',
        'return_at',
        'contact',
        'version',
        'release',
        'user_id',
        'customer_id',
        'form_id',
        'status',
        'completed_at',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function form()
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function returnAuthor()
    {
        return $this->belongsTo(User::class, 'return_author_id');
    }

    public function elements()
    {
        return $this->hasMany(Element::class, 'feedback_id');
    }

    /* =========================
     |  Helpers
     |========================= */

    public function getNamedValue($value, ElementType $type)
    {
        return match ($type->type) {
            'radio' => explode(';', $type->data)[$value] ?? null,
            default => $value,
        };
    }
}
