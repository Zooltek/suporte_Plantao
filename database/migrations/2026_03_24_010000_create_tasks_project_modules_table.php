<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks_project_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')
                ->constrained('tasks_projects')
                ->cascadeOnDelete();
            $table->foreignId('label_id')
                ->constrained('labels')
                ->cascadeOnDelete();
            $table->unique(['project_id', 'label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks_project_modules');
    }
};
