<?php

namespace App\Services\WhatsApp;

use App\Models\Company;
use App\Support\Phone\PhoneNumber;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyPhoneLookupService
{
    public function resolve(string $phone): ?Company
    {
        if (! config('whatsapp.chatbot.auto_identify_company_by_phone', true)) {
            return null;
        }

        $variants = PhoneNumber::variants($phone);

        if ($variants === []) {
            return null;
        }

        $company = $this->resolveUniqueByColumns($variants, ['customers.whatsapp_phone']);

        if ($company) {
            return $company;
        }

        return $this->resolveUniqueByPrimaryPhonesOrContacts($variants);
    }

    public function resolveId(string $phone): ?int
    {
        return $this->resolve($phone)?->id;
    }

    private function resolveUniqueByColumns(array $variants, array $columns): ?Company
    {
        $matches = Company::query()
            ->select(['customers.id', 'customers.name', 'customers.trade_name', 'customers.cnpj'])
            ->where(function (Builder $query) use ($variants, $columns): void {
                foreach ($columns as $index => $column) {
                    $method = $index === 0 ? 'whereIn' : 'orWhereIn';
                    $query->{$method}($this->normalizedPhoneExpression($column), $variants);
                }
            })
            ->orderBy('customers.id')
            ->limit(2)
            ->get();

        return $this->pickUniqueMatch($matches, $variants);
    }

    private function resolveUniqueByPrimaryPhonesOrContacts(array $variants): ?Company
    {
        $matches = Company::query()
            ->select(['customers.id', 'customers.name', 'customers.trade_name', 'customers.cnpj'])
            ->where(function (Builder $query) use ($variants): void {
                foreach (['customers.phone', 'customers.telephone_2'] as $index => $column) {
                    $method = $index === 0 ? 'whereIn' : 'orWhereIn';
                    $query->{$method}($this->normalizedPhoneExpression($column), $variants);
                }

                $query->orWhereHas('contacts', function (Builder $contactQuery) use ($variants): void {
                    $contactQuery->whereIn(
                        $this->normalizedPhoneExpression('customer_contacts.phone'),
                        $variants
                    );
                });
            })
            ->orderBy('customers.id')
            ->limit(2)
            ->get();

        return $this->pickUniqueMatch($matches, $variants);
    }

    private function pickUniqueMatch($matches, array $variants): ?Company
    {
        if ($matches->count() === 1) {
            return $matches->first();
        }

        if ($matches->count() > 1) {
            Log::warning('[WhatsApp] Telefone associado a múltiplos clientes. Identificação automática ignorada.', [
                'phone_variants' => $variants,
                'company_ids' => $matches->pluck('id')->all(),
            ]);
        }

        return null;
    }

    private function normalizedPhoneExpression(string $column)
    {
        return DB::raw(
            "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(COALESCE({$column}, ''), '+', ''), '(', ''), ')', ''), '-', ''), ' ', ''), '.', '')"
        );
    }
}
