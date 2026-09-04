<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->unsignedBigInteger('referenced_ticket_id')
                  ->nullable()
                  ->after('task_id')
                  ->comment('Chamado referente (chamado anterior vinculado)');

            $table->foreign('referenced_ticket_id')
                  ->references('id')
                  ->on('ticketit')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropForeign(['referenced_ticket_id']);
            $table->dropColumn('referenced_ticket_id');
        });
    }
};
