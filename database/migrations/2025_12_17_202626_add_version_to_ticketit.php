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
        Schema::table('ticketit', function (Blueprint $table) {
            $table->string('version')->nullable();
            $table->string('release')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropColumn(['version', 'release']);
        });
    }
};
