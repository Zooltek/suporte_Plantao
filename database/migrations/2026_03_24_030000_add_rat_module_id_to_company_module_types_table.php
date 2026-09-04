<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Liga cada CompanyModuleType (módulo do helpdesk/ticket) ao template RAT
 * correspondente em schedule_record_module.
 *
 * Resolução do ID collision: antes, o formulário de ticket passava
 * company_module_types.id diretamente para a query de elements, que esperava
 * schedule_record_module.id. Com esta FK, o Service resolve a ponte correta.
 *
 * Nullable porque módulos sem checklist RAT são válidos (não precisam de template).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_module_types', function (Blueprint $table) {
            $table->unsignedBigInteger('rat_module_id')
                ->nullable()
                ->after('sort_order')
                ->comment('FK para schedule_record_module — template RAT vinculado a este módulo');

            $table->foreign('rat_module_id')
                ->references('id')
                ->on('schedule_record_module')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('company_module_types', function (Blueprint $table) {
            $table->dropForeign(['rat_module_id']);
            $table->dropColumn('rat_module_id');
        });
    }
};
