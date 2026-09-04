<?php

namespace App\Models\Tasks\Changelog;

use App\Models\Tasks\Changelog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Version extends Model
{
    protected $table = 'tasks_changelog_versions';

    public $timestamps = true;

    protected $fillable = [
        'project_id',
        'user_id',
        'reference_date',
        'name',
        'description',
    ];

    protected $casts = [
        'reference_date' => 'datetime',
    ];

    /**
     * Relacionamento: versão possui vários changelogs.
     */
    public function changelogs(): HasMany
    {
        return $this->hasMany(Changelog::class, 'version_id');
    }
}
