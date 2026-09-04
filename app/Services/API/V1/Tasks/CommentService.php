<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\TaskCommentRepositoryInterface;
use App\Models\Tasks\Comment;
use Exception;

class CommentService
{
    public function __construct(
        private readonly TaskCommentRepositoryInterface $commentRepository,
    ) {}

    /**
     * Cria um novo comentário vinculado a uma tarefa.
     */
    public function createComment(array $data, int $userId): Comment
    {
        return $this->commentRepository->create($data, $userId);
    }

    /**
     * Exclui um comentário, garantindo que apenas o autor possa fazê-lo.
     */
    public function deleteComment(int $commentId, int $userId): bool
    {
        $comment = $this->commentRepository->findOrFail($commentId);

        if ($comment->user_id !== $userId) {
            throw new Exception('Unauthorized', 403);
        }

        return $this->commentRepository->delete($comment);
    }
}
