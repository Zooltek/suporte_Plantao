<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chats', 'ticket_id')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->foreignId('ticket_id')
                ->nullable()
                ->after('subject')
                ->constrained('ticketit')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chats', 'ticket_id')) {
            return;
        }

        Schema::table('chats', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropColumn('ticket_id');
        });
    }
};
