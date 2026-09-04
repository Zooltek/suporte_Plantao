<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Introduz a flag requires_agent em ticketit_statuses.
 *
 * Regra de negócio: status que representam trabalho ativo (Em Andamento,
 * Resolvido, Não Resolvido, Visita Técnica) exigem que um agente responsável
 * seja atribuído ao chamado. Status de espera (Pendente, Solicitação) não.
 *
 * Essa flag elimina o hardcode de IDs [2, 3, 5] em SaveTicketRequest,
 * seguindo o mesmo padrão de is_terminal, requires_schedule e requires_solution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->boolean('requires_agent')->default(false)->after('requires_solution');
        });

        // Status que exigem agente: Em Andamento (2), Resolvido (3), Não Resolvido (5)
        DB::table('ticketit_statuses')
            ->whereIn('id', [2, 3, 5])
            ->update(['requires_agent' => true]);

        // Visita Técnica: identificada por requires_schedule=true (sem hardcode de ID)
        DB::table('ticketit_statuses')
            ->where('requires_schedule', true)
            ->update(['requires_agent' => true]);
    }

    public function down(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->dropColumn('requires_agent');
        });
    }
};
