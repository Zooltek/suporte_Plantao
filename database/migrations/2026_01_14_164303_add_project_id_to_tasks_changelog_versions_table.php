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
        if (Schema::hasTable('tasks_changelog_versions')) {
            Schema::table('tasks_changelog_versions', function (Blueprint $table) {
                if (!Schema::hasColumn('tasks_changelog_versions', 'project_id')) {
                    $table->foreignId('project_id')
                          ->after('user_id')
                          ->constrained('tasks_projects')
                          ->cascadeOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks_changelog_versions')) {
            Schema::table('tasks_changelog_versions', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            });
        }
    }
};
