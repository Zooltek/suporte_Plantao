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
        if (!Schema::hasTable('tasks_projects')) {
            Schema::create('tasks_projects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('color');
                
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                
                $table->string('status')->default('1');
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks_projects');
    }
};
