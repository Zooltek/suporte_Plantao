<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->unsignedBigInteger('rat_module_id')
                ->nullable()
                ->after('module_id')
                ->comment('Template RAT efetivamente usado no chamado');

            $table->foreign('rat_module_id')
                ->references('id')
                ->on('schedule_record_module')
                ->nullOnDelete();
        });

        $moduleMap = DB::table('company_module_types')
            ->whereNotNull('rat_module_id')
            ->pluck('rat_module_id', 'id');

        foreach ($moduleMap as $companyModuleId => $ratModuleId) {
            DB::table('ticketit')
                ->where('module_id', $companyModuleId)
                ->whereNull('rat_module_id')
                ->update(['rat_module_id' => $ratModuleId]);
        }
    }

    public function down(): void
    {
        Schema::table('ticketit', function (Blueprint $table) {
            $table->dropForeign(['rat_module_id']);
            $table->dropColumn('rat_module_id');
        });
    }
};
