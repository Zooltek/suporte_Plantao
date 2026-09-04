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
        // Aplicando plural: ticketits
        $tableName = 'ticketit';

        if (Schema::hasTable($tableName)) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'task_id')) {
                    // Criando a conexão com a tabela tasks (plural)
                    $table->foreignId('task_id')
                          ->nullable()
                          ->constrained('tasks')
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
        $tableName = 'ticketit';

        if (Schema::hasColumn($tableName, 'task_id')) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['task_id']);
                $table->dropColumn('task_id');
            });
        }
    }
};
