<?php

namespace App\Support\Vite;

use Illuminate\Http\Request;

final class ViteRequestPolicy
{
    /**
     * Permite HMR apenas quando a requisição foi feita por loopback no próprio host.
     * Isso evita expor URLs localhost para usuários que acessam a aplicação via IP.
     */
    public static function shouldUseHotFile(?Request $request): bool
    {
        return self::isLocalRequest($request);
    }

    public static function isLocalRequest(?Request $request): bool
    {
        if (! $request instanceof Request) {
            return false;
        }

        if (self::isLoopbackValue($request->ip())) {
            return true;
        }

        return self::isLoopbackValue($request->getHost());
    }

    private static function isLoopbackValue(?string $value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return in_array(
            trim(strtolower($value), '[]'),
            ['localhost', '127.0.0.1', '::1'],
            true
        );
    }
}
