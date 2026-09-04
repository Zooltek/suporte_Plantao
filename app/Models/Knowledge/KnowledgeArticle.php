<?php

namespace App\Models\Knowledge;

use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\Knowledge\KnowledgeArticleFactory;

class KnowledgeArticle extends Model
{
    use HasFactory;


    protected $table = 'knowledge_articles';

    protected $fillable = [
        'author_id',
        'ticket_id',
        'category_id',
        'title',
        'problem',
        'solution',
        'tags',
        'visibility',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id', 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeSearch(Builder $query, string $term): Builder
    {
        return $query->where(function (Builder $q) use ($term) {
            $q->where('title', 'like', "%{$term}%")
              ->orWhere('problem', 'like', "%{$term}%")
              ->orWhere('solution', 'like', "%{$term}%")
              ->orWhere('tags', 'like', "%{$term}%");
        });
    }

    public function getTagsArrayAttribute(): array
    {
        if (empty($this->tags)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $this->tags)));
    }

    protected static function newFactory(): KnowledgeArticleFactory
    {
        return KnowledgeArticleFactory::new();
    }
}
