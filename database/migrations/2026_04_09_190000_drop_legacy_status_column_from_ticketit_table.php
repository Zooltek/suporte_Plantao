<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove a coluna legada `status` (ENUM) da tabela `ticketit`.
 *
 * Motivo: a coluna remanescente do pacote ticketit original colidia com o
 * relacionamento `status()` do modelo Ticket. Em Eloquent, atributos do DB
 * têm precedência sobre relações de mesmo nome em `getAttribute()`, portanto
 * `$ticket->status` retornava a string ENUM (default 'Aberto') em vez do
 * modelo Status relacionado — causando o bug em que todos os tickets
 * apareciam como "Aberto" na interface, independentemente do status real.
 *
 * A lógica de status é 100% controlada por `status_id` + tabela
 * `ticketit_statuses`, portanto a coluna `status` é pura herança morta.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('ticketit', 'status')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('ticketit', 'status')) {
            Schema::table('ticketit', function (Blueprint $table) {
                $table->enum('status', [
                    'Aberto',
                    'Fechado',
                    'Aguardando',
                    'Em Atendimento',
                    'Rejeitado',
                    'Aguardando Cliente',
                ])->default('Aberto')->after('content');
            });
        }
    }
};
