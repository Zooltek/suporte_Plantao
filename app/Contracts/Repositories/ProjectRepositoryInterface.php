<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface
{
    /**
     * Retorna todos os projetos ativos ordenados por nome (ASC).
     */
    public function getActiveProjects(): Collection;

    /**
     * Retorna os project_ids em que o usuário participa.
     *
     * @return int[]
     */
    public function getUserProjectIds(int $userId): array;

    /**
     * Busca a participação do usuário num projeto; retorna null se não existir.
     */
    public function findParticipation(int $projectId, int $userId): ?UserProject;

    /**
     * Remove a participação do usuário no projeto.
     */
    public function deleteParticipation(UserProject $participation): void;

    /**
     * Cria a participação do usuário no projeto.
     */
    public function createParticipation(int $projectId, int $userId): UserProject;
}
