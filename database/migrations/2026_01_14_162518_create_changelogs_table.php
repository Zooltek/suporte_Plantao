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
        if (!Schema::hasTable('changelogs')) {
            Schema::create('changelogs', function (Blueprint $table) {
                $table->id();
                $table->text('content')->nullable();
                
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                $table->foreignId('project_id')->constrained('tasks_projects')->cascadeOnDelete();
                
                $table->tinyInteger('status')->default(1);
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('changelogs');
    }
};
