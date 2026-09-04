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
                $table->boolean('bold')->default(false)->after('content');
                $table->boolean('blank')->default(false)->after('bold');
                $table->boolean('title')->default(false)->after('blank');
                $table->string('color', 20)->nullable()->after('title'); // ex: #FFFFFF ou 'red'
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
                $table->dropColumn(['bold', 'blank', 'title', 'color']);
            });
        }
    }
};
