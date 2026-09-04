<?php

namespace App\Contracts\Repositories;

use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use App\Models\Tasks\Project;
use Illuminate\Database\Eloquent\Collection;

interface ChangelogRepositoryInterface
{
    /**
     * Retorna changelogs pendentes (sem versão), opcionalmente filtrados por projeto.
     */
    public function getPendingChangelogs(?int $projectId): Collection;

    /**
     * Busca changelog pelo ID. Lança ModelNotFoundException se não encontrado.
     */
    public function findChangelog(int $id): Changelog;

    /**
     * Busca projeto pelo ID. Lança ModelNotFoundException se não encontrado.
     */
    public function findProject(int $id): Project;

    /**
     * Cria e persiste um novo changelog.
     */
    public function createChangelog(array $data): Changelog;

    /**
     * Atualiza campos de um changelog e retorna a instância atualizada.
     */
    public function updateChangelog(Changelog $changelog, array $data): Changelog;

    /**
     * Inativa (soft-delete lógico) um changelog.
     */
    public function deleteChangelog(Changelog $changelog): bool;

    /**
     * Retorna versões de um projeto com changelogs ordenados.
     */
    public function getVersionsByProject(int $projectId): Collection;

    /**
     * Retorna o valor máximo de sort_order para um projeto.
     */
    public function getMaxSortOrder(int $projectId): int;
}
