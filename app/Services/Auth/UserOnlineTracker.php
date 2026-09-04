<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Cache;

class UserOnlineTracker
{
    public const REGISTRY_KEY = 'online_users_registry';
    public const USER_HIT_PREFIX = 'user_online_hit:';
    public const DEFAULT_TTL_MINUTES = 5; // 5 minutos sem requisições = inativo

    /**
     * Registra ou atualiza a atividade recente do usuário.
     */
    public static function hit(int $userId, bool $force = false): void
    {
        if ($userId <= 0) {
            return;
        }

        $hitKey = self::USER_HIT_PREFIX . $userId;

        // Limita a frequência de gravação no registro global (uma vez a cada 10 segundos por usuário), a menos que forçado (ex: login)
        if ($force || !Cache::has($hitKey)) {
            Cache::put($hitKey, true, 10);

            $now = now()->timestamp;
            $cutoff = $now - (self::DEFAULT_TTL_MINUTES * 60);

            try {
                $registry = Cache::get(self::REGISTRY_KEY, []);
                if (!is_array($registry)) {
                    $registry = [];
                }

                $registry[$userId] = $now;

                // Limpa usuários que expiraram
                foreach ($registry as $id => $timestamp) {
                    if ($timestamp < $cutoff) {
                        unset($registry[$id]);
                    }
                }

                Cache::forever(self::REGISTRY_KEY, $registry);
            } catch (\Throwable) {
                // Falha silenciosa em caso de indisponibilidade transitória de cache
            }
        }
    }

    /**
     * Remove o usuário do registro de online imediatamente (ex: logout).
     */
    public static function forget(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        try {
            Cache::forget(self::USER_HIT_PREFIX . $userId);

            $registry = Cache::get(self::REGISTRY_KEY, []);
            if (is_array($registry) && isset($registry[$userId])) {
                unset($registry[$userId]);
                Cache::forever(self::REGISTRY_KEY, $registry);
            }
        } catch (\Throwable) {
            // Falha silenciosa
        }
    }

    /**
     * Retorna a lista de IDs de usuários ativos nos últimos X minutos.
     *
     * @return array<int>
     */
    public static function getActiveUserIds(int $ttlMinutes = self::DEFAULT_TTL_MINUTES): array
    {
        try {
            $registry = Cache::get(self::REGISTRY_KEY, []);
            if (!is_array($registry)) {
                return [];
            }

            $cutoff = now()->subMinutes($ttlMinutes)->timestamp;
            $activeIds = [];

            foreach ($registry as $id => $timestamp) {
                if ($timestamp >= $cutoff) {
                    $activeIds[] = (int) $id;
                }
            }

            return array_values(array_unique($activeIds));
        } catch (\Throwable) {
            return [];
        }
    }
}
