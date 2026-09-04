<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos recebidos do sistema financeiro no cadastro de cliente.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'financeiro_id')) {
                $table->unsignedBigInteger('financeiro_id')->nullable()->unique()
                    ->after('id')->comment('Id do cliente no sistema financeiro (chave externa da integração)');
            }

            if (! Schema::hasColumn('customers', 'codigo_empresarial')) {
                $table->string('codigo_empresarial', 100)->nullable()
                    ->after('trade_name')->comment('Código empresarial (financeiro)');
            }

            if (! Schema::hasColumn('customers', 'city_registration')) {
                $table->string('city_registration', 50)->nullable()
                    ->after('cnpj')->comment('Inscrição Estadual');
            }

            if (! Schema::hasColumn('customers', 'postal_code')) {
                $table->string('postal_code', 20)->nullable()
                    ->after('city')->comment('CEP');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            foreach (['codigo_empresarial', 'city_registration', 'postal_code'] as $column) {
                if (Schema::hasColumn('customers', $column)) {
                    $table->dropColumn($column);
                }
            }

            if (Schema::hasColumn('customers', 'financeiro_id')) {
                $table->dropUnique('customers_financeiro_id_unique');
                $table->dropColumn('financeiro_id');
            }
        });
    }
};
