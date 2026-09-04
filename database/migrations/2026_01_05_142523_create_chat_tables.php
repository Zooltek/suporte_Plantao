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
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->longText('content')->nullable();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });

        Schema::create('chat_participants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('socket_id')->nullable();
            $table->string('session');
            $table->string('display_name')->nullable();
            $table->string('email')->nullable();
            $table->string('token');
        });

        Schema::create('chat_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('owner_id');
            $table->string('desc')->nullable();
            $table->unsignedBigInteger('status_id');
            $table->string('session');
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('password');
            $table->dateTime('expire_at');
            $table->dateTime('closed_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_participants');
        Schema::dropIfExists('chat_statuses');
        Schema::dropIfExists('chats');
    }
};
