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
        // Tabela de Etiquetas (Labels)
        Schema::create('labels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Alterado para boolean para seguir o padrão de status ativo/inativo
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });

        // Tabela Pivô: Relacionamento Muitos-para-Muitos entre Tasks e Labels
        Schema::create('label_task', function (Blueprint $table) {
            $table->id();
            
            // Relacionamento com a Etiqueta
            $table->foreignId('label_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Relacionamento com a Tarefa (Referenciando a tabela 'tasks' criada anteriormente)
            $table->foreignId('task_id')
                  ->constrained()
                  ->cascadeOnDelete();
                  
            $table->timestamps();
            
            // Garante que não existam duplicatas da mesma etiqueta na mesma tarefa
            $table->unique(['label_id', 'task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('label_task');
        Schema::dropIfExists('labels');
    }
};

