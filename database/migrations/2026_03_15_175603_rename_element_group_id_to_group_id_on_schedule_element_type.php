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
        // Só renomeia se a coluna legada ainda existir (MySQL de produção).
        // Em ambientes de test (SQLite) a migration original já cria como group_id.
        if (Schema::hasColumn('schedule_record_element_type', 'element_group_id')) {
            Schema::table('schedule_record_element_type', function (Blueprint $table) {
                $table->renameColumn('element_group_id', 'group_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('schedule_record_element_type', 'group_id')) {
            Schema::table('schedule_record_element_type', function (Blueprint $table) {
                $table->renameColumn('group_id', 'element_group_id');
            });
        }
    }
};
