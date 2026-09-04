<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\UserPermissionRepositoryInterface;
use App\Models\User;

class UserPermissionService
{
    public function __construct(
        private readonly UserPermissionRepositoryInterface $repository
    ) {}

    /**
     * Atualiza as permissões de implantação do usuário.
     *
     * Escopo exclusivo: deployment_admin e can_manage_implementation.
     * Os campos ticketit_agent e ticketit_admin são gerenciados
     * pelo painel admin (/admin/users) via campo role.
     */
    public function updatePermissions(User $user, array $permissions): bool
    {
        return $this->repository->updatePermissions($user, $permissions);
    }
}
