<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUS_NAME = 'Visita Técnica';

    /**
     * Adiciona a flag requires_schedule e insere o status "Visita Técnica".
     *
     * Statuses com requires_schedule=true disparam a criação de agendamento
     * logo após o chamado ser salvo. A decisão fica no banco — sem hardcode de ID.
     */
    public function up(): void
    {
        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->boolean('requires_schedule')->default(false)->after('is_terminal');
        });

        if (! DB::table('ticketit_statuses')->where('name', self::STATUS_NAME)->exists()) {
            DB::table('ticketit_statuses')->insert([
                'name'              => self::STATUS_NAME,
                'color'             => '#f97316',
                'is_terminal'       => false,
                'requires_schedule' => true,
                'created_at'        => now(),
                'updated_at'        => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('ticketit_statuses')->where('name', self::STATUS_NAME)->delete();

        Schema::table('ticketit_statuses', function (Blueprint $table) {
            $table->dropColumn('requires_schedule');
        });
    }
};
