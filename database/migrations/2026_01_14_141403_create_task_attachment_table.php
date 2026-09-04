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
        if (!Schema::hasTable('task_attachment')) {
            Schema::create('task_attachment', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('mime');
                
                $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
                
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
        Schema::dropIfExists('task_attachment');
    }
};
