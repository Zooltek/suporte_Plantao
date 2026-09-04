<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Nicolaslopezj\Searchable\SearchableTrait;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Solution extends Model
{
  //  use SearchableTrait;

    protected $table = 'solutions';

    protected $fillable = [
        'title',
        'content',
        'created_at',
        'category_id',
        'author_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected $searchable = [
        'columns' => [
            'solutions.title'   => 10,
            'solutions.content' => 10,
            'solutions.tags'    => 10,
        ],
    ];

    /** Relacionamentos */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class, 'solution_id');
    }

    /** Métodos auxiliares */
    public function getDate(): string
    {
        $result = substr($this->created_at->format('l'), 0, 3);
        return $result . $this->created_at->format(', d \\d\\e M');
    }

    public function getTime(): string
    {
        return $this->created_at->format('H:i');
    }
}
