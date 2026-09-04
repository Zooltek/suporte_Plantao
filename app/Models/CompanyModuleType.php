<?php

namespace App\Models;

use App\Models\Schedule\Module as RatModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class CompanyModuleType extends Model
{
    use HasFactory;

    protected $table = 'company_module_types';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'sort_order',
        'rat_module_id',
    ];

    protected $casts = [
        'is_active'     => 'boolean',
        'rat_module_id' => 'integer',
    ];

    /**
     * Template RAT (schedule_record_module) vinculado a este módulo do helpdesk.
     * Nullable — módulos sem checklist técnico não precisam de template.
     */
    public function ratModule(): BelongsTo
    {
        return $this->belongsTo(RatModule::class, 'rat_module_id');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class, 'customer_module_type', 'module_type_id', 'customer_id');
    }
}
