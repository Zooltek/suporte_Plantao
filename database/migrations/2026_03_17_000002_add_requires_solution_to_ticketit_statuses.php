<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduz a flag requires_solution em ticketit_statuses.
 *
 * Regra de negócio: apenas o status "Resolvido" exige o preenchimento
 * do campo "Escopo da Solução Aplicada". Status como "Em Andamento" e
 * "Não Resolvido" registram o problema (trouble), mas não possuem solução
 * a documentar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->boolean('requires_solution')->default(false)->after('requires_schedule');
        });

        DB::table('ticketit_statuses')
            ->where('name', 'Resolvido')
            ->update(['requires_solution' => true]);
    }

    public function down(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->dropColumn('requires_solution');
        });
    }
};
