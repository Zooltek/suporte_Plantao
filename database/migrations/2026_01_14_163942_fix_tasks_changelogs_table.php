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
        if (Schema::hasTable('tasks_changelogs')) {
            Schema::table('tasks_changelogs', function (Blueprint $table) {
                if (Schema::hasColumn('tasks_changelogs', 'task_id')) {
                    $table->foreignId('task_id')->nullable()->unsigned()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks_changelogs')) {
            Schema::table('tasks_changelogs', function (Blueprint $table) {
                $table->foreignId('task_id')->nullable(false)->unsigned()->change();
            });
        }
    }
};
