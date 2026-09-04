<?php

namespace App\Models;

use App\Models\Tasks\Label;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'user_department';

    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'is_default',
        'is_crm',
        'is_feedback',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_crm' => 'boolean',
        'is_feedback' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (Department $department): void {
            if ($department->is_default) {
                throw new \InvalidArgumentException('O departamento padrão não pode ser excluído.');
            }
        });
    }

    /**
     * Relacionamento: Departamento tem muitos módulos (labels)
     */
    public function labels(): HasMany
    {
        return $this->hasMany(Label::class, 'department_id');
    }

    public function scopeCrm($query)
    {
        return $query->where('is_crm', true);
    }

    public function scopeFeedback($query)
    {
        return $query->where('is_feedback', true);
    }

    public static function crmDepartmentId(): ?int
    {
        return static::query()->where('is_crm', true)->value('id');
    }

    public static function feedbackDepartmentId(): ?int
    {
        return static::query()->where('is_feedback', true)->value('id');
    }
}
