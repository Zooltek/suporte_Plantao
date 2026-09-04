<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\HelpdeskCompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Database\Eloquent\Collection;

class CompanyService
{
    public function __construct(
        private readonly HelpdeskCompanyRepositoryInterface $repository,
    ) {}

    /**
     * Retorna todas as empresas com cache para otimizar listagens administrativas.
     */
    public function getAllCompanies(): Collection
    {
        return $this->repository->getAll();
    }

    /**
     * Cria ou vincula empresa e responsável em uma única transação.
     */
    public function storeCompany(array $data): Company
    {
        return $this->repository->create($data);
    }

    /**
     * Realiza o vínculo entre usuário e empresa.
     */
    public function linkUserToCompany(int $userId, int $companyId, bool $isResponsible): void
    {
        $this->repository->linkUser($userId, $companyId, $isResponsible);
    }

    /**
     * Busca cidades por estado com cache Redis.
     */
    public function getCitiesByState(int $stateId): Collection
    {
        return $this->repository->getCitiesByState($stateId);
    }
}
