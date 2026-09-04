<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface ProfileRepositoryInterface
{
    /**
     * Persiste os dados básicos do perfil (name, email, avatar).
     */
    public function updateProfile(User $user, array $data): bool;

    /**
     * Persiste a nova senha (já hashada) do usuário.
     */
    public function updatePassword(User $user, string $hashedPassword): bool;
}
