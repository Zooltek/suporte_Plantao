<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ajuste do contrato de integração financeira (SPEC-001 — Ponto 2).
 *
 * - Adiciona `state_registration` para Inscrição Estadual (IE).
 * - `city_registration` permanece para o Código IBGE do município.
 *
 * Dados históricos em `city_registration` NÃO são migrados automaticamente:
 * o sistema financeiro reenviará os registros com o novo payload corrigido.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('customers', 'state_registration')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('state_registration', 50)->nullable()
                    ->after('city_registration')
                    ->comment('Inscrição Estadual (IE) — enviada pelo financeiro via state_registration');
            });
        }

        if (Schema::hasColumn('customers', 'city_registration')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('city_registration', 50)->nullable()
                    ->comment('Código IBGE do município — enviado pelo financeiro via city_registration')
                    ->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'state_registration')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('state_registration');
            });
        }

        if (Schema::hasColumn('customers', 'city_registration')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('city_registration', 50)->nullable()
                    ->comment('Inscrição Estadual')
                    ->change();
            });
        }
    }
};
