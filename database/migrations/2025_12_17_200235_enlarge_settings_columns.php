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
        // tornar colunas value e default maiores
        Schema::table('ticketit_settings', function (Blueprint $table) {
            $table->mediumText('value')->change();
            $table->mediumText('default')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketit_settings', function (Blueprint $table) {
            $table->string('value')->change();
            $table->string('default')->change();
        });
    }
};
