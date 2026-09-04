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
        Schema::create('user_ratings', function (Blueprint $table) {
            $table->id(); // BigIncrements (id) por padrão
            
            // Relacionamento com a tabela de usuários
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Valor da avaliação (ex: 1 a 5)
            $table->unsignedTinyInteger('rating');
            
            // Relacionamento com a tabela de feedbacks (ajustar nome da tabela se necessário)
            $table->foreignId('feedback_id')
                  ->constrained('ticketit_agent_rate')
                  ->cascadeOnDelete();

            $table->timestamps(); // Adicionado para auditoria (created_at/updated_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_ratings');
    }
};
