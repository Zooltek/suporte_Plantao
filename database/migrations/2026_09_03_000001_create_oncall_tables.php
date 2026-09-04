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
        // 1. Tabela de Turnos de Plantão / Sobreaviso
        Schema::create('oncall_shifts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('user_id')->index(); // Agente
            $table->dateTime('started_at');
            $table->dateTime('ended_at')->nullable();
            $table->integer('total_standby_minutes')->default(0); // Tempo líquido em sobreaviso
            $table->integer('total_worked_minutes')->default(0);  // Tempo efetivo em atendimentos
            $table->string('status', 30)->default('active');      // active, finished, approved
            $table->text('notes')->nullable();
            $table->timestamps();

            // Chave estrangeira para users
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // 2. Tabela de Atendimentos Realizados no Plantão
        Schema::create('oncall_attendances', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('oncall_shift_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index(); // Agente que atendeu
            $table->unsignedBigInteger('customer_id')->nullable()->index(); // Clientes (customers / Company)
            $table->string('customer_name_fallback', 255)->nullable(); // Se cliente for avulso/não listado
            $table->string('contact_name', 150)->nullable();
            $table->unsignedBigInteger('category_id')->nullable()->index();
            $table->unsignedBigInteger('sub_category_id')->nullable()->index();
            $table->dateTime('started_at');
            $table->dateTime('ended_at');
            $table->integer('duration_minutes')->default(0);
            $table->text('trouble'); // Problema relatado
            $table->text('solution')->nullable(); // O que foi feito
            $table->boolean('is_resolved')->default(true);
            $table->unsignedBigInteger('status_id')->nullable()->index(); // Status do chamado
            $table->unsignedBigInteger('ticket_id')->nullable()->index(); // ID gerado na tabela ticketit
            $table->dateTime('synced_at')->nullable();
            $table->timestamps();

            $table->foreign('oncall_shift_id')->references('id')->on('oncall_shifts')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oncall_attendances');
        Schema::dropIfExists('oncall_shifts');
    }
};
