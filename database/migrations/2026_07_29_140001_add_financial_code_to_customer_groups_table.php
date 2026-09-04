<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajuste do contrato de integração financeira (SPEC-001 — Ponto 3).
 *
 * Adiciona `financial_code` à tabela `customer_groups` para armazenar
 * o código externo do Grupo Empresarial gerado pelo sistema financeiro.
 *
 * - O `hash` interno permanece inalterado (identificador interno do sistema).
 * - `financial_code` é o código estável do financeiro (ex: BFC27A6401563A).
 * - Index único garante integridade e previne race condition em upserts.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customer_groups', 'financial_code')) {
            Schema::table('customer_groups', function (Blueprint $table) {
                $table->string('financial_code', 100)->nullable()
                    ->after('name')
                    ->comment('Código externo do Grupo Empresarial no sistema financeiro (ex: BFC27A6401563A)');
            });
        }

        if (! Schema::hasIndex('customer_groups', ['financial_code'], 'unique')) {
            Schema::table('customer_groups', function (Blueprint $table) {
                $table->unique('financial_code');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('customer_groups', ['financial_code'], 'unique')) {
            Schema::table('customer_groups', function (Blueprint $table) {
                $table->dropUnique('customer_groups_financial_code_unique');
            });
        }

        if (Schema::hasColumn('customer_groups', 'financial_code')) {
            Schema::table('customer_groups', function (Blueprint $table) {
                $table->dropColumn('financial_code');
            });
        }
    }
};
