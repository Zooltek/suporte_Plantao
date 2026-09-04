<?php

namespace App\Repositories;

use App\Contracts\Repositories\StateRepositoryInterface;
use App\Models\State;

class StateRepository implements StateRepositoryInterface
{
    /**
     * Mapa código IBGE da UF → sigla. Referência fixa (27 unidades federativas).
     */
    private const IBGE_TO_UF = [
        11 => 'RO', 12 => 'AC', 13 => 'AM', 14 => 'RR', 15 => 'PA', 16 => 'AP',
        17 => 'TO', 21 => 'MA', 22 => 'PI', 23 => 'CE', 24 => 'RN', 25 => 'PB',
        26 => 'PE', 27 => 'AL', 28 => 'SE', 29 => 'BA', 31 => 'MG', 32 => 'ES',
        33 => 'RJ', 35 => 'SP', 41 => 'PR', 42 => 'SC', 43 => 'RS', 50 => 'MS',
        51 => 'MT', 52 => 'GO', 53 => 'DF',
    ];

    public function findIdByIbgeCode(int $ibgeCode): ?int
    {
        $uf = self::IBGE_TO_UF[$ibgeCode] ?? null;

        if ($uf === null) {
            return null;
        }

        return State::query()->where('abbreviation', $uf)->value('id');
    }
}
