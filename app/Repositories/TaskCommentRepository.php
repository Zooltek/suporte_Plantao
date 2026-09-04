<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\TaskCommentRepositoryInterface;
use App\Models\Tasks\Comment;

class TaskCommentRepository implements TaskCommentRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function create(array $data, int $userId): Comment
    {
        return Comment::create([
            'content' => $data['content'],
            'task_id' => $data['task_id'],
            'author_id' => $userId,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $commentId): Comment
    {
        return Comment::findOrFail($commentId);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(Comment $comment): bool
    {
        return (bool) $comment->delete();
    }
}
