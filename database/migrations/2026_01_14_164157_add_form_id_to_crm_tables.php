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
        // Criar a tabela de formulários
        if (!Schema::hasTable('crm_feedback_forms')) {
            Schema::create('crm_feedback_forms', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Adicionar form_id à tabela de feedback
        if (Schema::hasTable('crm_feedbacks')) {
            Schema::table('crm_feedbacks', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_feedbacks', 'form_id')) {
                    $table->foreignId('form_id')->default(1)->constrained('crm_feedback_forms');
                }
            });
        }

        // Adicionar form_id à tabela de tipos de elementos
        if (Schema::hasTable('crm_feedback_element_types')) {
            Schema::table('crm_feedback_element_types', function (Blueprint $table) {
                if (!Schema::hasColumn('crm_feedback_element_types', 'form_id')) {
                    $table->foreignId('form_id')->default(1)->constrained('crm_feedback_forms');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('crm_feedbacks')) {
            Schema::table('crm_feedbacks', function (Blueprint $table) {
                if (Schema::hasColumn('crm_feedbacks', 'form_id')) {
                    $table->dropForeign(['form_id']);
                    $table->dropColumn('form_id');
                }
            });
        }

        if (Schema::hasTable('crm_feedback_element_types')) {
            Schema::table('crm_feedback_element_types', function (Blueprint $table) {
                if (Schema::hasColumn('crm_feedback_element_types', 'form_id')) {
                    $table->dropForeign(['form_id']);
                    $table->dropColumn('form_id');
                }
            });
        }

        Schema::dropIfExists('crm_feedback_forms');
    }
};
