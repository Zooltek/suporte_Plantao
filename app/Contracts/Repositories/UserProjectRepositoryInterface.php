<?php

namespace App\Contracts\Repositories;

use App\Models\Tasks\Project;
use App\Models\Tasks\UserProject;
use Illuminate\Database\Eloquent\Collection;

interface UserProjectRepositoryInterface
{
    public function allForUser(int $userId): Collection;

    public function existsForUser(int $userId, int $projectId): bool;

    public function findProject(int $projectId): Project;

    public function create(array $data): UserProject;

    public function findByIdAndUser(int $id, int $userId): UserProject;

    public function findByProjectAndUser(int $projectId, int $userId): ?UserProject;

    public function delete(UserProject $userProject): bool;
}
