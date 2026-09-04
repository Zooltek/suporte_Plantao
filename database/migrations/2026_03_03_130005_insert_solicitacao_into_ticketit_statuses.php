<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Insere apenas se não existir status com esse nome
        $exists = DB::table('ticketit_statuses')->where('name', 'Solicitação')->exists();

        if (!$exists) {
            DB::table('ticketit_statuses')->insert([
                'name'       => 'Solicitação',
                'color'      => '#8b5cf6',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ticketit_statuses')->where('name', 'Solicitação')->delete();
    }
};
