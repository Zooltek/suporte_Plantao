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
        if (!Schema::hasTable('tasks_changelog_versions')) {
            Schema::create('tasks_changelog_versions', function (Blueprint $table) {
                $table->id();
                $table->text('name')->nullable();
                
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                
                $table->dateTime('reference_date');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks_changelog_versions');
    }
};
