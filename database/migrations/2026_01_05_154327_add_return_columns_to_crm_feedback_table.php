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
        Schema::table('crm_feedback', function (Blueprint $table) {
            // Conteúdo da resposta do feedback
            $table->longText('return_content')->nullable()->after('content');

            // ID do autor da resposta, vinculado à tabela de usuários
            $table->foreignId('return_author_id')
                  ->nullable()
                  ->after('return_content')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('crm_feedback', function (Blueprint $table) {
            $table->dropForeign(['return_author_id']);
            $table->dropColumn(['return_content', 'return_author_id']);
        });
    }
};

