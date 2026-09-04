<?php

namespace App\Services\API\V1\Tasks;

use App\Contracts\Repositories\UserProjectRepositoryInterface;
use App\Models\Tasks\UserProject;
use Illuminate\Database\Eloquent\Collection;
use Exception;

class UserProjectService
{
    public function __construct(
        private readonly UserProjectRepositoryInterface $repository,
    ) {}

    /**
     * Retorna a lista de projetos do usuário ordenados pelo nome do projeto.
     */
    public function getUserProjects(int $userId): Collection
    {
        return $this->repository->allForUser($userId);
    }

    /**
     * Vincula o usuário a um projeto, aplicando a cor customizada ou a cor padrão do projeto.
     */
    public function assignProject(int $userId, int $projectId, ?string $color): UserProject
    {
        if ($this->repository->existsForUser($userId, $projectId)) {
            throw new Exception('Usuário já vinculado a este projeto', 422);
        }

        $project = $this->repository->findProject($projectId);

        return $this->repository->create([
            'user_id'    => $userId,
            'project_id' => $projectId,
            'color'      => $color ?? $project->color,
        ]);
    }

    /**
     * Remove o vínculo do projeto pelo ID da tabela `user_projects`.
     */
    public function deleteById(int $id, int $userId): bool
    {
        $userProject = $this->repository->findByIdAndUser($id, $userId);

        return $this->repository->delete($userProject);
    }

    /**
     * Remove o vínculo do projeto pelo ID da tabela `projects`.
     */
    public function dissociateProject(int $projectId, int $userId): bool
    {
        $userProject = $this->repository->findByProjectAndUser($projectId, $userId);

        if (!$userProject) {
            throw new Exception('Vínculo não encontrado', 422);
        }

        return $this->repository->delete($userProject);
    }
}
