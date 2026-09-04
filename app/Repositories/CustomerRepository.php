<?php

namespace App\Repositories;

use App\Contracts\Repositories\CustomerRepositoryInterface;
use App\Models\Customer;
use Closure;
use Illuminate\Support\Facades\DB;

class CustomerRepository implements CustomerRepositoryInterface
{
    public function upsertByFinanceiroId(int $financeiroId, array $attributes): Customer
    {
        return Customer::updateOrCreate(
            ['financeiro_id' => $financeiroId],
            $attributes,
        );
    }

    public function setActiveStatus(int $financeiroId, bool $active): ?Customer
    {
        $customer = Customer::query()->where('financeiro_id', $financeiroId)->first();

        if ($customer === null) {
            return null;
        }

        $customer->update(['is_active' => $active]);

        return $customer;
    }

    public function syncFinancialContacts(Customer $customer, array $contacts): void
    {
        $customer->contacts()->where('origin', 'financeiro')->delete();

        foreach ($contacts as $contact) {
            $customer->contacts()->create([
                'name' => $contact['name'],
                'phone' => null,
                'email' => $contact['email'] ?: null,
                'origin' => 'financeiro',
                'is_main' => false,
            ]);
        }
    }

    public function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
