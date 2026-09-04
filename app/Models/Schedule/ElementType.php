<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementType extends Model
{
    use HasFactory;
    protected $table = 'schedule_record_element_type';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'label',
        'type',
        'module_id',
        'group_id',
    ];

    /**
     * Relacionamento: cada ElementType pertence a um grupo.
     */
    public function group(): BelongsTo
    {
        return $this->belongsTo(ElementGroup::class, 'group_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'module_id');
    }

    public function elements(): HasMany
    {
        return $this->hasMany(Element::class, 'element_id');
    }
}
