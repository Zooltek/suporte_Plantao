<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\UserPermissionRepositoryInterface;
use App\Models\User;

class UserPermissionRepository implements UserPermissionRepositoryInterface
{
    /**
     * Atualiza deployment_admin e can_manage_implementation do usuário.
     */
    public function updatePermissions(User $user, array $permissions): bool
    {
        return $user->update([
            'deployment_admin'          => $permissions['deployment_admin'],
            'can_manage_implementation' => $permissions['can_manage_implementation'],
        ]);
    }
}
