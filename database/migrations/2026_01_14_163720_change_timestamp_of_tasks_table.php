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
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'delivery_at')) {
                    $table->dateTimeTz('delivery_at')->nullable()->change();
                }
                
                if (Schema::hasColumn('tasks', 'request_at')) {
                    $table->dateTimeTz('request_at')->nullable()->change();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->dateTimeTz('delivery_at')->nullable(false)->change();
                $table->dateTimeTz('request_at')->nullable()->change();
            });
        }
    }
};
