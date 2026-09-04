<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface UserPermissionRepositoryInterface
{
    /**
     * Atualiza deployment_admin e can_manage_implementation do usuário.
     */
    public function updatePermissions(User $user, array $permissions): bool;
}
