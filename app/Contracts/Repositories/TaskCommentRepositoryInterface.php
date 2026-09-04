<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tasks\Comment;

interface TaskCommentRepositoryInterface
{
    /**
     * Cria e persiste um novo comentário de tarefa.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $userId): Comment;

    /**
     * Busca um comentário pelo id; lança ModelNotFoundException se não encontrado.
     */
    public function findOrFail(int $commentId): Comment;

    /**
     * Exclui o comentário do banco e retorna true em caso de sucesso.
     */
    public function delete(Comment $comment): bool;
}
