<?php

namespace App\Models\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    protected $table = 'user_settings';
    protected $fillable = ['user_id', 'slug', 'value', 'default'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}