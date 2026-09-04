<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {

            if (!Schema::hasColumn('customers', 'trade_name')) {
                $table->string('trade_name', 150)->nullable()->after('name')->comment('Nome Fantasia');
            }

            if (!Schema::hasColumn('customers', 'email')) {
                $table->string('email', 150)->nullable()->unique()->after('cnpj')->comment('Email de contato');
            }

            if (!Schema::hasColumn('customers', 'phone')) {
                $table->string('phone', 20)->nullable()->after('email')->comment('Telefone');
            }

            if (!Schema::hasColumn('customers', 'address')) {
                $table->string('address', 255)->nullable()->after('phone')->comment('Endereço completo');
            }

            if (!Schema::hasColumn('customers', 'created_at')) {
                $table->timestamp('created_at')->nullable()->after('address');
            }

            if (!Schema::hasColumn('customers', 'updated_at')) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
            }
        });
    }

    public function down(): void
    {
        $columns = collect([
            'trade_name',
            'email',
            'phone',
            'address',
            'created_at',
            'updated_at',
        ])->filter(fn (string $column) => Schema::hasColumn('customers', $column))
            ->values()
            ->all();

        if ($columns === []) {
            return;
        }

        if (in_array('email', $columns, true)) {
            $driver = Schema::getConnection()->getDriverName();

            if ($driver === 'sqlite') {
                DB::statement('DROP INDEX IF EXISTS customers_email_unique');
            } else {
                Schema::table('customers', function (Blueprint $table) {
                    $table->dropUnique('customers_email_unique');
                });
            }
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn($columns);
        });
    }
};
