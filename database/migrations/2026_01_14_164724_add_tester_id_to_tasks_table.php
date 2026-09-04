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
                if (!Schema::hasColumn('tasks', 'tester_id')) {
                    // Criando a coluna vinculada à tabela users
                    $table->foreignId('tester_id')
                          ->nullable()
                          ->after('user_id')
                          ->constrained('users')
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
        if (Schema::hasTable('tasks')) {
            Schema::table('tasks', function (Blueprint $table) {
                if (Schema::hasColumn('tasks', 'tester_id')) {
                    $table->dropForeign(['tester_id']);
                    $table->dropColumn('tester_id');
                }
            });
        }
    }
};
