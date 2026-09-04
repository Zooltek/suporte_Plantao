<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('changelogs') && !Schema::hasTable('tasks_changelogs')) {
            Schema::rename('changelogs', 'tasks_changelogs');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks_changelogs')) {
            Schema::rename('tasks_changelogs', 'changelogs');
        }
    }
};
