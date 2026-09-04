<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_bot_messages', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('step')->index();
            $table->text('text');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('whatsapp_message_macros', function (Blueprint $table) {
            $table->id();
            $table->string('command', 80)->unique();
            $table->text('text');
            $table->foreignId('department_id')
                ->nullable()
                ->constrained('user_department')
                ->nullOnDelete();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('whatsapp_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_settings');
        Schema::dropIfExists('whatsapp_message_macros');
        Schema::dropIfExists('whatsapp_bot_messages');
    }
};
