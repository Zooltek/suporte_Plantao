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
        if (Schema::hasTable('tasks_notifications')) {
            Schema::table('tasks_notifications', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks_notifications', 'icon')) {
                    $table->text('icon')->nullable()->after('id');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks_notifications')) {
            Schema::table('tasks_notifications', function (Blueprint $table) {
                if (Schema::hasColumn('tasks_notifications', 'icon')) {
                    $table->dropColumn('icon');
                }
            });
        }
    }
};
