<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Garante integridade referencial entre is_terminal e requires_schedule.
 *
 * Regra de negócio: um status terminal (chamado encerrado) nunca deve exigir
 * agendamento. Caso o banco esteja inconsistente por rollback parcial ou
 * execução fora de ordem das migrations anteriores, esta migration corrige.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('ticketit_statuses')
            ->where('is_terminal', true)
            ->update(['requires_schedule' => false]);
    }

    public function down(): void
    {
        // Sem rollback: não há estado anterior consistente a restaurar.
    }
};
