<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends User
{
    protected $table = 'users'; 

    /**
     * Relacionamento: autor possui várias soluções.
     */
    public function solutions(): HasMany
    {
        return $this->hasMany(Solution::class, 'author_id');
    }
}
