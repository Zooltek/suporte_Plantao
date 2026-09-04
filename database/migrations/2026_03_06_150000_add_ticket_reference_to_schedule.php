<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->unsignedBigInteger('ticket_id')->nullable()->after('customer_id');
            $table->boolean('requires_admin_confirmation')->default(false)->after('status');

            $table->foreign('ticket_id')
                ->references('id')
                ->on('ticketit')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('schedule', function (Blueprint $table) {
            $table->dropForeign(['ticket_id']);
            $table->dropColumn(['ticket_id', 'requires_admin_confirmation']);
        });
    }
};
