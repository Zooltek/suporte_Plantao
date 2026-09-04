<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Solution;
use App\Models\Like;

interface SolutionRepositoryInterface
{
    /**
     * Busca a solução pelo ID; lança ModelNotFoundException se ausente.
     */
    public function findOrFail(int $solutionId): Solution;

    /**
     * Retorna o registro Like existente para um par user/solution, ou null.
     */
    public function findExistingLike(int $userId, int $solutionId): ?Like;

    /**
     * Cria um novo registro Like.
     */
    public function createLike(int $userId, int $solutionId, int $isLike): Like;

    /**
     * Atualiza o valor do like existente.
     */
    public function updateLike(Like $like, int $isLike): bool;

    /**
     * Incrementa o contador de likes da solução.
     */
    public function incrementLikes(Solution $solution): void;

    /**
     * Incrementa o contador de dislikes da solução.
     */
    public function incrementDislikes(Solution $solution): void;

    /**
     * Decrementa o contador de likes da solução.
     */
    public function decrementLikes(Solution $solution): void;

    /**
     * Decrementa o contador de dislikes da solução.
     */
    public function decrementDislikes(Solution $solution): void;
}
