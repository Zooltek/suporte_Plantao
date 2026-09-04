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
            $table->foreignId('origin_id')
                  ->default(1)
                  ->constrained('ticketit_origin')
                  ->cascadeOnDelete();

            $table->longText('trouble')->nullable();
            $table->longText('solution')->nullable();
            $table->string('contact')->nullable();
            $table->longText('obs')->nullable();
            $table->boolean('visible')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropForeign(['origin_id']);
            $table->dropColumn([
                'origin_id',
                'trouble',
                'solution',
                'contact',
                'obs',
                'visible',
            ]);
        });
    }
};
