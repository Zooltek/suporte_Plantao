<?php

namespace App\Contracts\Repositories;

interface StateRepositoryInterface
{
    /**
     * Resolve o id interno do estado (states.id) a partir do código IBGE da UF.
     *
     * O sistema financeiro envia o código IBGE (ex.: 32 = ES); internamente os
     * estados usam ids próprios (1..27). Retorna null quando o código é
     * desconhecido ou a UF não está cadastrada.
     */
    public function findIdByIbgeCode(int $ibgeCode): ?int;
}
