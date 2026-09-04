<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedule_record_task', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->foreignId('record_id')->constrained('schedule_record')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'record_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedule_record_task');
    }
};
