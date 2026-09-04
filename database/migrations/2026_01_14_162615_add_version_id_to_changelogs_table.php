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
        if (Schema::hasTable('changelogs')) {
            Schema::table('changelogs', function (Blueprint $table) {
                if (!Schema::hasColumn('changelogs', 'version_id')) {
                    $table->foreignId('version_id')
                          ->nullable()
                          ->after('project_id')
                          ->constrained('tasks_changelog_versions')
                          ->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('changelogs', 'version_id')) {
            Schema::table('changelogs', function (Blueprint $table) {
                $table->dropForeign(['version_id']);
                $table->dropColumn('version_id');
            });
        }
    }
};
