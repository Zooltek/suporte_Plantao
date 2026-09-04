<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\BoletoRepositoryInterface;
use App\Models\Boleto;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class BoletoRepository implements BoletoRepositoryInterface
{
    /**
     * {@inheritDoc}
     */
    public function findUserWithCompany(int $userId): User
    {
        return User::findOrFail($userId);
    }

    /**
     * {@inheritDoc}
     */
    public function getBoletosByCompany(int $companyId): Collection
    {
        $cacheKey = "user_boletos_company_{$companyId}";

        return Cache::remember($cacheKey, 1800, function () use ($companyId) {
            return Boleto::where('customer_id', $companyId)
                ->orderBy('due_date', 'asc')
                ->get();
        });
    }
}
