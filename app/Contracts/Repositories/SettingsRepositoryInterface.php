<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;

interface SettingsRepositoryInterface
{
    /**
     * Persiste os dados básicos do usuário (email, refresh_rate, password)
     * e sincroniza as preferências de interface dentro de uma transação atômica.
     */
    public function updateProfileAndPreferences(User $user, array $validatedData): void;

    /**
     * Retorna o mapa slug => valor das preferências do usuário.
     */
    public function getForUser(int $userId): array;
}
