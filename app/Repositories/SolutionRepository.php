<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\SolutionRepositoryInterface;
use App\Models\Like;
use App\Models\Solution;
use Illuminate\Support\Facades\DB;

class SolutionRepository implements SolutionRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findOrFail(int $solutionId): Solution
    {
        return Solution::findOrFail($solutionId);
    }

    /**
     * {@inheritDoc}
     */
    public function findExistingLike(int $userId, int $solutionId): ?Like
    {
        return Like::where('user_id', $userId)
            ->where('solution_id', $solutionId)
            ->first();
    }

    /**
     * {@inheritDoc}
     */
    public function createLike(int $userId, int $solutionId, int $isLike): Like
    {
        return Like::create([
            'user_id'     => $userId,
            'solution_id' => $solutionId,
            'like'        => $isLike,
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function updateLike(Like $like, int $isLike): bool
    {
        return $like->update(['like' => $isLike]);
    }

    /**
     * {@inheritDoc}
     */
    public function incrementLikes(Solution $solution): void
    {
        $solution->increment('likes');
    }

    /**
     * {@inheritDoc}
     */
    public function incrementDislikes(Solution $solution): void
    {
        $solution->increment('dislikes');
    }

    /**
     * {@inheritDoc}
     */
    public function decrementLikes(Solution $solution): void
    {
        $solution->decrement('likes');
    }

    /**
     * {@inheritDoc}
     */
    public function decrementDislikes(Solution $solution): void
    {
        $solution->decrement('dislikes');
    }
}
