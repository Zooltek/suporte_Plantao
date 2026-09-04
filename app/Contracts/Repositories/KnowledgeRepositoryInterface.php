<?php

namespace App\Contracts\Repositories;

use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Ticket\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KnowledgeRepositoryInterface
{
    public function paginate(?string $search, ?int $categoryId): LengthAwarePaginator;

    public function create(array $data): KnowledgeArticle;

    public function update(KnowledgeArticle $article, array $data): KnowledgeArticle;

    public function incrementViews(KnowledgeArticle $article): void;

    public function delete(KnowledgeArticle $article): void;
}
