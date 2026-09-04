<?php

namespace App\Contracts\Repositories;

use App\Models\Tasks\Changelog;
use App\Models\Tasks\Changelog\Version;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

interface VersionRepositoryInterface
{
    /**
     * Lista versões, opcionalmente filtradas por projeto, com changelogs ordenados.
     */
    public function getVersionsByProject(?int $projectId): EloquentCollection;

    /**
     * Busca versão pelo ID. Lança ModelNotFoundException se não encontrada.
     */
    public function findVersion(int $id): Version;

    /**
     * Cria e persiste uma nova versão.
     */
    public function createVersion(array $data): Version;

    /**
     * Vincula changelogs à versão (bulk update) e retorna a versão carregada com changelogs.
     */
    public function assignChangelogsToVersion(Version $version, Collection $changelogs): Version;

    /**
     * Busca a versão mais recente de um projeto (por reference_date DESC).
     */
    public function getLatestVersion(int $projectId): ?Version;

    /**
     * Retorna changelogs ativos sem versão de um projeto criados até uma data.
     */
    public function getPendingChangelogsUpTo(int $projectId, Carbon $date): EloquentCollection;
}
