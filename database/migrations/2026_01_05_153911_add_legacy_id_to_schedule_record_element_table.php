<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedule_record_element', function (Blueprint $table) {
            // Adicionando o ID legado como bigInteger para compatibilidade total
            // Colocamos after('id') para manter a organização visual no início da tabela
            $table->bigInteger('legacy_id')
                  ->nullable()
                  ->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule_record_element', function (Blueprint $table) {
            $table->dropColumn('legacy_id');
        });
    }
};
