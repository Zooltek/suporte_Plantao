<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\BoletoRepositoryInterface;
use Illuminate\Support\Collection;

class BoletoService
{
    public function __construct(
        private readonly BoletoRepositoryInterface $repository,
    ) {}

    /**
     * Busca os boletos da empresa do usuário com Cache.
     * O cache é indexado pelo ID da empresa para não misturar dados.
     */
    public function getBoletosByUserId(int $userId): Collection
    {
        $user = $this->repository->findUserWithCompany($userId);

        // Verifica se o usuário tem company_id associado
        if (empty($user->company_id)) {
            return collect();
        }

        return $this->repository->getBoletosByCompany((int) $user->company_id);
    }
}
