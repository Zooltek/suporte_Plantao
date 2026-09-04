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
        $tableName = 'tasks';

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'seen')) {
                    $table->boolean('seen')->default(false)->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableName = 'tasks';

        if (Schema::hasColumn($tableName, 'seen')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('seen');
            });
        }
    }
};
