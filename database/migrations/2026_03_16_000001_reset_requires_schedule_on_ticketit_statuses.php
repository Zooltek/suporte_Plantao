<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Garante que apenas o status "Visita Técnica" tenha requires_schedule = true.
     * Corrige eventuais dados inconsistentes que disparariam o redirect indevido.
     */
    public function up(): void
    {
        DB::table('ticketit_statuses')->update(['requires_schedule' => false]);

        DB::table('ticketit_statuses')
            ->where('name', 'Visita Técnica')
            ->update(['requires_schedule' => true]);
    }

    public function down(): void
    {
        // Irreversível — down não tem como saber o estado anterior de cada registro.
    }
};
