<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ProjectService
{
    public function __construct(
        private readonly ProjectRepositoryInterface $projectRepository,
    ) {}

    /**
     * Retorna os projetos ativos, opcionalmente marcando a participação do usuário.
     */
    public function getActiveProjects(bool $checkParticipation, ?int $userId = null): Collection
    {
        $projects = $this->projectRepository->getActiveProjects();

        if ($checkParticipation && $userId) {
            $this->markUserParticipation($projects, $userId);
        }

        return $projects;
    }

    /**
     * Alterna a participação do usuário no projeto.
     * Retorna true se o usuário entrou, false se saiu.
     */
    public function toggleParticipation(int $projectId, int $userId): bool
    {
        $participation = $this->projectRepository->findParticipation($projectId, $userId);

        if ($participation) {
            $this->projectRepository->deleteParticipation($participation);
            return false;
        }

        $this->projectRepository->createParticipation($projectId, $userId);

        return true;
    }

    /**
     * Marca no objeto de projetos se o usuário faz parte deles.
     */
    private function markUserParticipation(Collection $projects, int $userId): void
    {
        $userProjectIds = $this->projectRepository->getUserProjectIds($userId);

        $projects->map(function ($project) use ($userProjectIds) {
            $project->already = in_array($project->id, $userProjectIds, true);
            return $project;
        });
    }
}
