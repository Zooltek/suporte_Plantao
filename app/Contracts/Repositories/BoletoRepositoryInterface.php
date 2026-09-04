<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Support\Collection;

interface BoletoRepositoryInterface
{
    /**
     * Busca o usuário pelo ID; lança ModelNotFoundException se ausente.
     */
    public function findUserWithCompany(int $userId): User;

    /**
     * Retorna boletos de uma empresa ordenados por due_date com cache.
     */
    public function getBoletosByCompany(int $companyId): Collection;
}
