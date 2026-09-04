<?php

namespace App\Contracts\Repositories;

use App\Models\Customer;
use Closure;

interface CustomerRepositoryInterface
{
    /**
     * Cria ou atualiza o cliente pela chave externa do financeiro (idempotente).
     */
    public function upsertByFinanceiroId(int $financeiroId, array $attributes): Customer;

    /**
     * Substitui somente os contatos mantidos pelo Financeiro.
     * Contatos cadastrados pelos atendentes permanecem intactos.
     *
     * @param  array<int, array{name: string, email: string|null}>  $contacts
     */
    public function syncFinancialContacts(Customer $customer, array $contacts): void;

    /**
     * Define a situação ativa do cliente identificado pelo id do financeiro.
     * Retorna null quando o cliente não existe.
     */
    public function setActiveStatus(int $financeiroId, bool $active): ?Customer;

    /**
     * Executa o callback dentro de uma transação de banco de dados.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function transaction(Closure $callback): mixed;
}
