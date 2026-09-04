<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adiciona as colunas description e status à tabela ticketit_origin.
 * Essas colunas são referenciadas pelo HelpdeskService e OriginController
 * mas estavam ausentes na migration original.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit_origin', function (Blueprint $table) {
            $table->string('description', 255)->nullable()->after('name');
            $table->boolean('status')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('ticketit_origin', function (Blueprint $table) {
            $table->dropColumn(['description', 'status']);
        });
    }
};
