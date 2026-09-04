<?php

namespace App\Services\Agent;

use App\Contracts\Repositories\SettingsRepositoryInterface;
use App\Models\User;

class SettingsService
{
    public function __construct(
        private readonly SettingsRepositoryInterface $repository
    ) {}

    /**
     * Atualiza o perfil e as preferências do usuário de forma atômica.
     * Toda a persistência é delegada ao Repository (incluindo a transação).
     */
    public function updateProfileAndPreferences(User $user, array $validatedData): void
    {
        $this->repository->updateProfileAndPreferences($user, $validatedData);
    }
}
