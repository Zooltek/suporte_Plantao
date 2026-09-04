<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Company;
use App\Models\City;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface HelpdeskCompanyRepositoryInterface
{
    /**
     * Retorna todas as empresas com cache.
     */
    public function getAll(): Collection;

    /**
     * Cria uma empresa em transação e invalida cache.
     */
    public function create(array $data): Company;

    /**
     * Vincula usuário a uma empresa; opcionalmente define o responsável.
     */
    public function linkUser(int $userId, int $companyId, bool $isResponsible): void;

    /**
     * Retorna cidades de um estado com cache.
     */
    public function getCitiesByState(int $stateId): Collection;
}
