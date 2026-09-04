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
        Schema::create('ticketit_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('ticketit_priorities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('ticketit_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color');
            $table->string('avatar')->nullable();
            $table->timestamps();
        });

        // Tabela Pivot
        Schema::create('ticketit_categories_users', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained('ticketit_categories')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'user_id']);
        });

        Schema::create('ticketit', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->longText('content');
            $table->foreignId('status_id')->constrained('ticketit_statuses');
            $table->foreignId('priority_id')->constrained('ticketit_priorities');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('agent_id')->nullable()->constrained('users');
            $table->foreignId('category_id')->constrained('ticketit_categories');
            $table->unsignedBigInteger('sub_category_id')->nullable();
            $table->integer('rate_id')->default(0);
            $table->string('files')->nullable();
            $table->string('conversation_id_list')->nullable();
            $table->unsignedBigInteger('company_id')->index();
            $table->timestamps();
        });

        Schema::create('ticketit_comments', function (Blueprint $table) {
            $table->id();
            $table->text('content');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('ticketit')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('ticketit_audits', function (Blueprint $table) {
            $table->id();
            $table->text('operation');
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('ticketit')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('ticketit_agent_rate', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('ticketit_file', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('extension', 10);
            $table->foreignId('ticket_id')->constrained('ticketit')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Ordem inversa para evitar erros de Foreign Key
        Schema::dropIfExists('ticketit_file');
        Schema::dropIfExists('ticketit_agent_rate');
        Schema::dropIfExists('ticketit_audits');
        Schema::dropIfExists('ticketit_comments');
        Schema::dropIfExists('ticketit');
        Schema::dropIfExists('ticketit_categories_users');
        Schema::dropIfExists('ticketit_categories');
        Schema::dropIfExists('ticketit_priorities');
        Schema::dropIfExists('ticketit_statuses');
    }
};
