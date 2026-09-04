<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona a flag is_terminal à tabela ticketit_statuses.
     *
     * Statuses terminais representam um chamado encerrado/finalizado.
     * Ao marcar um chamado com status terminal, completed_at é preenchido
     * e nenhuma alteração de status posterior deve ser aceita sem agente.
     *
     * Statuses pré-existentes marcados como terminais:
     *   id=3  → Resolvido
     *   id=14 → Fechado
     */
    public function up(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->boolean('is_terminal')->default(false)->after('color');
        });

        DB::table('ticketit_statuses')
            ->whereIn('id', [3, 14])
            ->update(['is_terminal' => true]);
    }

    public function down(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->dropColumn('is_terminal');
        });
    }
};
