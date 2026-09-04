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
        if (Schema::hasTable('crm_feedback') && !Schema::hasColumn('crm_feedback', 'return_at')) {
            Schema::table('crm_feedback', function (Blueprint $table) {
                $table->timestamp('return_at')->nullable()->after('return_author_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crm_feedback') && Schema::hasColumn('crm_feedback', 'return_at')) {
            Schema::table('crm_feedback', function (Blueprint $table) {
                $table->dropColumn('return_at');
            });
        }
    }
};
