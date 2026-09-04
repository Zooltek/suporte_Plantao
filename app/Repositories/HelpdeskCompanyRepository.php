<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\HelpdeskCompanyRepositoryInterface;
use App\Models\City;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HelpdeskCompanyRepository implements HelpdeskCompanyRepositoryInterface
{
    protected const COMPANIES_CACHE_KEY = 'helpdesk_companies_all';

    /**
     * {@inheritDoc}
     */
    public function getAll(): Collection
    {
        return Cache::remember(self::COMPANIES_CACHE_KEY, 3600, fn () => Company::all());
    }

    /**
     * {@inheritDoc}
     */
    public function create(array $data): Company
    {
        return DB::transaction(function () use ($data) {
            $company = Company::create([
                'name'        => $data['name'],
                'cnpj'        => $data['cnpj'],
                'responsible' => $data['responsible'],
                'zipcode'     => $data['zipcode'],
                'address'     => $data['address'],
                'state_id'    => $data['state'],
                'city_id'     => $data['city'],
                'desc'        => $data['obs'] ?? null,
            ]);

            Cache::forget(self::COMPANIES_CACHE_KEY);

            return $company;
        });
    }

    /**
     * {@inheritDoc}
     */
    public function linkUser(int $userId, int $companyId, bool $isResponsible): void
    {
        DB::transaction(function () use ($userId, $companyId, $isResponsible) {
            $user = User::findOrFail($userId);
            $user->update(['company_id' => $companyId]);

            if ($isResponsible) {
                Company::where('id', $companyId)->update(['responsible' => $userId]);
                Cache::forget(self::COMPANIES_CACHE_KEY);
            }
        });
    }

    /**
     * {@inheritDoc}
     */
    public function getCitiesByState(int $stateId): Collection
    {
        return Cache::remember("cities_state_{$stateId}", 86400, function () use ($stateId) {
            return City::where('state', $stateId)->orderBy('name')->get();
        });
    }
}
