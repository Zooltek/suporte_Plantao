<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\ProfileRepositoryInterface;
use App\Models\User;

class ProfileRepository implements ProfileRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function updateProfile(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * {@inheritDoc}
     */
    public function updatePassword(User $user, string $hashedPassword): bool
    {
        return $user->update(['password' => $hashedPassword]);
    }
}
