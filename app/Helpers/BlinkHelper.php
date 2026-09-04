<?php

namespace App\Helpers;

use App\Models\Blink;
use Illuminate\Support\Collection;

class BlinkHelper
{
    /**
     * Verifica e remove um blink (notificação rápida), retornando se existia.
     */
    public static function popBlink(int $userId, string $hash): bool
    {
        $blink = Blink::where([
            'user_id' => $userId,
            'hash'    => $hash,
            'status'  => 1
        ])->first();

        if ($blink) {
            return (bool) $blink->delete();
        }

        return false;
    }

    /**
     * Adiciona um novo blink para um usuário específico.
     */
    public static function pushBlink(int $userId, string $hash): bool
    {
        Blink::firstOrCreate([
            'user_id' => $userId,
            'hash'    => $hash
        ], [
            'status'  => 1
        ]);

        return true;
    }

    /**
     * Adiciona blinks em massa para uma lista de usuários de forma otimizada.
     */
    public static function massPushBlink(iterable $users, string $hash): bool
    {
        $data = [];
        $now = now();

        foreach ($users as $user) {
            $data[] = [
                'user_id'    => is_object($user) ? $user->id : $user,
                'hash'       => $hash,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($data)) {
            // Otimização: Insere ou ignora duplicados em uma única query
            Blink::upsert($data, ['user_id', 'hash'], ['status', 'updated_at']);
        }

        return true;
    }
}
