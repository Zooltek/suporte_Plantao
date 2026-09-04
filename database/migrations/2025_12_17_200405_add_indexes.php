<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add indexes for better performance.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            // índices úteis que não entram em conflito com foreign keys
            $table->index('subject');
            $table->index('completed_at');
        });

        Schema::table('ticketit_comments', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('ticket_id');
        });

        Schema::table('ticketit_settings', function (Blueprint $table) {
            $table->index('lang');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropIndex(['subject']);
            $table->dropIndex(['completed_at']);
        });

        Schema::table('ticketit_comments', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['ticket_id']);
        });

        Schema::table('ticketit_settings', function (Blueprint $table) {
            $table->dropIndex(['lang']);
            $table->dropIndex(['slug']);
        });
    }
};
