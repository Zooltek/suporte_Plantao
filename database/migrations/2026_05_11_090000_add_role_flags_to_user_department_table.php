<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_department', function (Blueprint $table) {
            $table->boolean('is_crm')->default(false)->after('is_default');
            $table->boolean('is_feedback')->default(false)->after('is_crm');
        });

        // Migra a constante CRM_DEPARTMENT_ID=3 para uma flag persistida,
        // mantendo a semântica histórica do registro seedado.
        DB::table('user_department')
            ->where('id', 3)
            ->update(['is_crm' => true, 'is_feedback' => true]);
    }

    public function down(): void
    {
        Schema::table('user_department', function (Blueprint $table) {
            $table->dropColumn(['is_crm', 'is_feedback']);
        });
    }
};
