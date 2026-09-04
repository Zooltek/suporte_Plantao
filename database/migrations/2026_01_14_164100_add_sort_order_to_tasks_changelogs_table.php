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
                if (!Schema::hasColumn('tasks_changelogs', 'sort_order')) {
                    $table->integer('sort_order')->unsigned()->default(0)->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('tasks_changelogs', 'sort_order')) {
            Schema::table('tasks_changelogs', function (Blueprint $table) {
                $table->dropColumn('sort_order');
            });
        }
    }
};
