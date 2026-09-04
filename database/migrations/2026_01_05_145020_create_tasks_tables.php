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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            
            // Auto-relacionamento (Subtarefas)
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->nullOnDelete();
            
            // Autor da tarefa (quem criou)
            $table->foreignId('author_id')
                  ->constrained('users');
            
            // Usuário atribuído (responsável)
            $table->foreignId('user_id')
                  ->constrained('users');
            
            // Status com limite de caracteres para performance
            $table->string('status', 20)->default('pen');
            
            $table->timestamps();
        });

        Schema::create('task_comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            
            // Relação com a tarefa
            $table->foreignId('task_id')
                  ->constrained()
                  ->cascadeOnDelete();
            
            // Autor do comentário
            $table->foreignId('author_id')
                  ->constrained('users');
                  
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_comments');
        Schema::dropIfExists('tasks');
    }
};
