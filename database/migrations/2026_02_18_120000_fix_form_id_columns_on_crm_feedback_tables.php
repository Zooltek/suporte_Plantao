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
        if (!Schema::hasTable('crm_feedback_forms')) {
            Schema::create('crm_feedback_forms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('crm_feedback') && !Schema::hasColumn('crm_feedback', 'form_id')) {
            Schema::table('crm_feedback', function (Blueprint $table) {
                $table->foreignId('form_id')
                    ->default(1)
                    ->constrained('crm_feedback_forms');
            });
        }

        if (Schema::hasTable('crm_feedback_element_type') && !Schema::hasColumn('crm_feedback_element_type', 'form_id')) {
            Schema::table('crm_feedback_element_type', function (Blueprint $table) {
                $table->foreignId('form_id')
                    ->default(1)
                    ->constrained('crm_feedback_forms');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crm_feedback') && Schema::hasColumn('crm_feedback', 'form_id')) {
            Schema::table('crm_feedback', function (Blueprint $table) {
                $table->dropConstrainedForeignId('form_id');
            });
        }

        if (Schema::hasTable('crm_feedback_element_type') && Schema::hasColumn('crm_feedback_element_type', 'form_id')) {
            Schema::table('crm_feedback_element_type', function (Blueprint $table) {
                $table->dropConstrainedForeignId('form_id');
            });
        }
    }
};
