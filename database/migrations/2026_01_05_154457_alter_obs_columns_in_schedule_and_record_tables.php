<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alterando a tabela 'schedule'
        Schema::table('schedule', function (Blueprint $table) {
            $table->longText('obs')->nullable()->change();
        });

        // Alterando a tabela 'schedule_record'
        Schema::table('schedule_record', function (Blueprint $table) {
            $table->longText('obs')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->string('obs')->nullable()->change();
        });

        Schema::table('schedule_record', function (Blueprint $table) {
            $table->string('obs')->nullable()->change();
        });
    }
};
