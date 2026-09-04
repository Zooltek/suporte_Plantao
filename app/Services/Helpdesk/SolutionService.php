<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\SolutionRepositoryInterface;
use Illuminate\Support\Facades\DB;

class SolutionService
{
    public function __construct(
        private readonly SolutionRepositoryInterface $repository,
    ) {}

    /**
     * Registra ou atualiza o "Like" de um usuário em uma solução.
     *
     * @param int $userId
     * @param int $solutionId
     * @param int $isLike (1 para like, 0 para dislike)
     */
    public function toggleLike(int $userId, int $solutionId, int $isLike): array
    {
        return DB::transaction(function () use ($userId, $solutionId, $isLike) {
            $solution     = $this->repository->findOrFail($solutionId);
            $existingLike = $this->repository->findExistingLike($userId, $solutionId);

            // Se já deu o mesmo tipo de feedback, bloqueia
            if ($existingLike && $existingLike->like == $isLike) {
                return ['success' => false, 'message' => 'Você já avaliou esta solução desta forma.'];
            }

            // Se mudou de ideia (era like e virou dislike ou vice-versa)
            if ($existingLike) {
                if ($isLike == 1) { // Mudando para Like
                    $this->repository->decrementDislikes($solution);
                    $this->repository->incrementLikes($solution);
                } else { // Mudando para Dislike
                    $this->repository->decrementLikes($solution);
                    $this->repository->incrementDislikes($solution);
                }
                $this->repository->updateLike($existingLike, $isLike);
            } else {
                // Primeira vez avaliando
                $this->repository->createLike($userId, $solutionId, $isLike);

                if ($isLike == 1) {
                    $this->repository->incrementLikes($solution);
                } else {
                    $this->repository->incrementDislikes($solution);
                }
            }

            return ['success' => true, 'message' => 'Obrigado pela sua avaliação!'];
        });
    }
}
