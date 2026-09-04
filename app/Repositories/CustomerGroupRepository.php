<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\CustomerGroupRepositoryInterface;
use App\Models\CustomerGroup;
use Illuminate\Support\Str;

/**
 * Acesso ao banco para Grupos Empresariais.
 *
 * O `financial_code` (chave única) é o elo estável entre o sistema financeiro
 * e o suporte. O `hash` interno é mantido por compatibilidade com o restante
 * do sistema (telas de agent, CRM etc.) e NÃO deve ser confundido com o código
 * externo do financeiro.
 */
final class CustomerGroupRepository implements CustomerGroupRepositoryInterface
{
    public function findByFinancialCode(string $code): ?CustomerGroup
    {
        return CustomerGroup::query()
            ->where('financial_code', $code)
            ->first();
    }

    public function upsertByFinancialCode(string $code, string $name): CustomerGroup
    {
        $timestamp = now();

        CustomerGroup::query()->upsert(
            [[
                'financial_code' => $code,
                'name' => $name,
                'hash' => Str::random(40),
                'status' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]],
            ['financial_code'],
            ['name', 'updated_at'],
        );

        return CustomerGroup::query()
            ->where('financial_code', $code)
            ->firstOrFail();
    }
}
