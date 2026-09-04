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
        if (!Schema::hasTable('tasks_notification')) {
            Schema::create('tasks_notification', function (Blueprint $table) {
                $table->id();
                $table->string('content')->nullable();
                $table->unsignedBigInteger('ref_id')->nullable();
                $table->string('kind');
                $table->foreignId('author_id')->constrained('users');
                $table->foreignId('user_id')->constrained('users');
                $table->tinyInteger('status')->default(1);
                $table->tinyInteger('seen')->default(0);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks_notification');
    }
};
