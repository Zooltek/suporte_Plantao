<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\CustomerGroup;

/**
 * Contrato de acesso ao banco para Grupos Empresariais.
 *
 * O sistema financeiro é o único responsável por gerar e gerenciar os códigos
 * de grupo. Este repositório oferece um ponto único e testável de acesso,
 * isolando a lógica de upsert do Service.
 */
interface CustomerGroupRepositoryInterface
{
    /**
     * Localiza um grupo pelo código externo do financeiro.
     * Retorna null quando ainda não existe localmente.
     */
    public function findByFinancialCode(string $code): ?CustomerGroup;

    /**
     * Cria ou atualiza um grupo a partir dos dados recebidos do financeiro.
     *
     * Fluxo:
     *   1. Busca pelo `financial_code` (chave estável do financeiro).
     *   2. Se não existir → cria com code e name.
     *   3. Se existir e o name mudou → atualiza o name.
     *
     * O upsert atômico, apoiado pela constraint única em `financial_code`,
     * protege contra criação duplicada em processamentos concorrentes.
     *
     * @param  string  $code  Código externo gerado pelo financeiro (ex: BFC27A6401563A).
     * @param  string  $name  Nome do Grupo Empresarial no financeiro.
     */
    public function upsertByFinancialCode(string $code, string $name): CustomerGroup;
}
