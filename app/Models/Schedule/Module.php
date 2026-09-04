<?php

namespace App\Models\Schedule;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    use HasFactory;

    protected $table = 'schedule_record_module';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
        'project',
    ];

    public function elementGroups(): HasMany
    {
        return $this->hasMany(ElementGroup::class, 'module_id', 'id');
    }

    public function elementTypes(): HasMany
    {
        return $this->hasMany(ElementType::class, 'module_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(
            Company::class,
            'customer_schedule_module',
            'schedule_module_id',
            'customer_id'
        );
    }
}
