<?php

namespace App\Models\Schedule;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ElementGroup extends Model
{
    use HasFactory;
    protected $table = 'schedule_record_element_group'; 

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    /**
     * Relacionamento: um grupo possui vários tipos de elementos.
     */
    public function elementTypes(): HasMany
    {
        return $this->hasMany(ElementType::class, 'group_id', 'id');
    }
}
