<?php

namespace App\Services\Helpdesk;

use App\Contracts\Repositories\HomeRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class HomeService
{
    protected const CACHE_TTL = 3600;

    public function __construct(
        private readonly HomeRepositoryInterface $repository,
    ) {}

    /**
     * Retorna os dados agregados para a Home com Cache.
     */
    public function getHomeData(): array
    {
        return Cache::remember('helpdesk_home_data', self::CACHE_TTL, function () {
            return [
                'categories'       => $this->repository->getRootCategories(),
                'solutions'        => $this->repository->getActiveSolutions(),
                'latest_solutions' => $this->repository->getLatestSolutions(4),
            ];
        });
    }
}
