<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedBigInteger('return_assigned_to')->nullable()->after('return_cel');
            $table->timestamp('return_scheduled_at')->nullable()->after('return_assigned_to');

            $table->foreign('return_assigned_to')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['return_assigned_to']);
            $table->dropColumn(['return_assigned_to', 'return_scheduled_at']);
        });
    }
};
