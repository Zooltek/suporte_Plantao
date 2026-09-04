<?php

namespace App\Services\Knowledge;

use App\Contracts\Repositories\KnowledgeRepositoryInterface;
use App\Models\Knowledge\KnowledgeArticle;
use App\Models\Ticket\Ticket;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;

class KnowledgeService
{
    public function __construct(
        private readonly KnowledgeRepositoryInterface $repository,
    ) {}

    public function list(?string $search, ?int $categoryId): LengthAwarePaginator
    {
        return $this->repository->paginate($search, $categoryId);
    }

    /**
     * Cria um artigo a partir de um ticket (botão "Incluir na EasyWiki").
     */
    public function createFromTicket(Ticket $ticket, array $data): KnowledgeArticle
    {
        return $this->repository->create([
            'author_id'   => Auth::id(),
            'ticket_id'   => $ticket->id,
            'category_id' => $ticket->category_id,
            'title'       => $data['title'],
            'problem'     => $data['problem'],
            'solution'    => $data['solution'],
            'tags'        => $data['tags'] ?? null,
            'visibility'  => $data['visibility'] ?? 'internal',
            'active'      => true,
        ]);
    }

    public function create(array $data): KnowledgeArticle
    {
        return $this->repository->create([
            'author_id'   => Auth::guard('admin')->id() ?? Auth::guard('web')->id() ?? Auth::id(),
            'ticket_id'   => $data['ticket_id'] ?? null,
            'category_id' => $data['category_id'] ?? null,
            'title'       => $data['title'],
            'problem'     => $data['problem'],
            'solution'    => $data['solution'],
            'tags'        => $data['tags'] ?? null,
            'visibility'  => $data['visibility'] ?? 'internal',
            'active'      => true,
        ]);
    }

    public function update(KnowledgeArticle $article, array $data): KnowledgeArticle
    {
        return $this->repository->update($article, [
            'category_id' => $data['category_id'] ?? null,
            'title'       => $data['title'],
            'problem'     => $data['problem'],
            'solution'    => $data['solution'],
            'tags'        => $data['tags'] ?? null,
            'visibility'  => $data['visibility'] ?? 'internal',
        ]);
    }

    public function incrementViews(KnowledgeArticle $article): void
    {
        $this->repository->incrementViews($article);
    }

    public function delete(KnowledgeArticle $article): void
    {
        $this->repository->delete($article);
    }
}
