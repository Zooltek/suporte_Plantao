<?php

namespace App\Repositories;

use App\Contracts\Repositories\KnowledgeRepositoryInterface;
use App\Models\Knowledge\KnowledgeArticle;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class KnowledgeRepository implements KnowledgeRepositoryInterface
{
    public function paginate(?string $search, ?int $categoryId): LengthAwarePaginator
    {
        return KnowledgeArticle::active()
            ->with('author', 'category')
            ->when($search, fn ($q) => $q->search($search))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('created_at', 'desc')
            ->paginate(20);
    }

    public function create(array $data): KnowledgeArticle
    {
        return KnowledgeArticle::create($data);
    }

    public function update(KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        $article->update($data);
        return $article->fresh(['author', 'category']);
    }

    public function incrementViews(KnowledgeArticle $article): void
    {
        KnowledgeArticle::where('id', $article->id)->increment('views');
        $article->refresh();
    }

    public function delete(KnowledgeArticle $article): void
    {
        $article->delete();
    }
}
