<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Insere o status "Não Resolvido" (id=5) na tabela ticketit_statuses.
 *
 * Regra de negócio: "Não Resolvido" representa um chamado encerrado sem
 * solução encontrada. Exige agente (requires_agent=true), não é terminal
 * no sentido de "resolvido", mas encerra o ciclo de atendimento.
 *
 * O id=5 já é referenciado no form Blade (/agent/ticket/create.blade.php)
 * e na migration add_requires_agent_to_ticketit_statuses.php. A ausência
 * desta linha causava o erro silencioso de status inválido no backend.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Garante idempotência — não duplica se já existir
        $exists = DB::table('ticketit_statuses')->where('id', 5)->exists();

        if (! $exists) {
            DB::table('ticketit_statuses')->insert([
                'id'                => 5,
                'name'              => 'Não Resolvido',
                'color'             => '#6B7280',
                'is_terminal'       => true,
                'requires_schedule' => false,
                'requires_solution' => false,
                'requires_agent'    => true,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ticketit_statuses')->where('id', 5)->delete();
    }
};
